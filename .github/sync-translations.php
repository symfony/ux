<?php

/*
 * Propagates the English messages of each Symfony UX package to all its locales.
 *
 * For each `<domain>.en.xlf` of a `src/<Package>/translations/` directory, every `<domain>.<locale>.xlf`
 * file of that directory is rewritten with all the English units, in the English order. Existing targets are
 * kept, and a unit missing from a locale gets the English source as target, with `state="needs-translation"`.
 *
 * Run it after adding a message to an English catalogue. Then machine-translate the new targets
 * and replace their state with `state="needs-review-translation"`, so native speakers review them.
 *
 * Usage: php .github/sync-translations.php
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

if ('cli' !== \PHP_SAPI) {
    echo "This script can only be run from the command line.\n";
    exit(1);
}

function readHeader(string $file): ?DOMElement
{
    $document = new DOMDocument();
    $document->preserveWhiteSpace = false;
    $document->load($file);

    $header = $document->getElementsByTagName('header')->item(0);

    return $header instanceof DOMElement ? $header : null;
}

function dumpXliff(string $domain, MessageCatalogue $catalogue, ?DOMElement $header): string
{
    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;

    $xliff = $dom->appendChild($dom->createElement('xliff'));
    $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');
    $xliff->setAttribute('version', '1.2');

    $file = $xliff->appendChild($dom->createElement('file'));
    $file->setAttribute('source-language', 'en');
    $file->setAttribute('target-language', str_replace('_', '-', $catalogue->getLocale()));
    $file->setAttribute('datatype', 'plaintext');
    $file->setAttribute('original', $domain);

    if (null !== $header) {
        $file->appendChild($dom->importNode($header, true));
    }

    $body = $file->appendChild($dom->createElement('body'));
    foreach ($catalogue->all($domain) as $key => $target) {
        $metadata = $catalogue->getMetadata($key, $domain);
        if (isset($metadata['notes'])) {
            throw new LogicException(sprintf('Unit "%s" of the "%s" catalogue has notes, which this script does not keep.', $metadata['id'], $domain));
        }

        $unit = $body->appendChild($dom->createElement('trans-unit'));
        $unit->setAttribute('id', $metadata['id']);
        if ($key !== $metadata['source']) {
            $unit->setAttribute('resname', $key);
        }

        $unit->appendChild($dom->createElement('source'))->appendChild($dom->createTextNode($metadata['source']));

        $targetElement = $unit->appendChild($dom->createElement('target'));
        foreach ($metadata['target-attributes'] ?? [] as $name => $value) {
            $targetElement->setAttribute($name, $value);
        }
        $targetElement->appendChild(1 === preg_match('/[&<>]/', $target) ? $dom->createCDATASection($target) : $dom->createTextNode($target));
    }

    return preg_replace('/^ +/m', '$0$0', $dom->saveXML());
}

$loader = new XliffFileLoader();

foreach (glob(__DIR__.'/../src/*/translations/*.en.xlf') as $englishFile) {
    $domain = basename($englishFile, '.en.xlf');
    $english = $loader->load($englishFile, 'en', $domain);
    file_put_contents($englishFile, dumpXliff($domain, $english, readHeader($englishFile)));

    foreach (glob(dirname($englishFile).'/'.$domain.'.*.xlf') as $file) {
        $locale = substr(basename($file), strlen($domain) + 1, -strlen('.xlf'));
        if ('en' === $locale) {
            continue;
        }

        $catalogue = $loader->load($file, $locale, $domain);
        $synced = new MessageCatalogue($locale);

        foreach ($english->all($domain) as $key => $englishTarget) {
            $englishMetadata = $english->getMetadata($key, $domain);

            if ($catalogue->defines($key, $domain)) {
                $translation = $catalogue->get($key, $domain);
                $metadata = $catalogue->getMetadata($key, $domain);
            } else {
                $translation = $englishMetadata['source'];
                $metadata = ['target-attributes' => ['state' => 'needs-translation']];
            }

            $metadata['id'] = $englishMetadata['id'];
            $metadata['source'] = $englishMetadata['source'];

            $synced->set($key, $translation, $domain);
            $synced->setMetadata($key, $metadata, $domain);
        }

        file_put_contents($file, dumpXliff($domain, $synced, readHeader($file)));
    }
}
