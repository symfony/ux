<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Turbo\Artist;
use App\Entity\Turbo\Book;
use App\Entity\Turbo\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/ux-turbo', name: 'app_ux_turbo_')]
final class TurboController extends AbstractController
{
    #[Route('/drive', name: 'drive')]
    public function drive(
        #[MapQueryParameter] int $page = 1,
    ): Response {
        if (2 === $page) {
            return $this->render('ux_turbo/drive_page_2.html.twig', [
                'current_time' => new \DateTimeImmutable()->format(\DateTimeInterface::RFC3339_EXTENDED),
            ]);
        }

        return $this->render('ux_turbo/drive.html.twig', [
            'current_time' => new \DateTimeImmutable()->format(\DateTimeInterface::RFC3339_EXTENDED),
        ]);
    }

    #[Route('/frame', name: 'frame')]
    public function frame(): Response
    {
        return $this->render('ux_turbo/frame.html.twig');
    }

    #[Route('/frame-content', name: 'frame_content')]
    public function frameContent(): Response
    {
        return $this->render('ux_turbo/frame_content.html.twig');
    }

    #[Route('/stream', name: 'stream')]
    public function streamAction(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('ux_turbo/stream_response.html.twig');
            }

            return $this->redirectToRoute('app_ux_turbo_stream');
        }

        return $this->render('ux_turbo/stream.html.twig');
    }

    #[Route('/broadcast/books', name: 'broadcast_books', methods: ['GET', 'POST'])]
    public function broadcastBooks(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->request->get('id')) {
                if (!$book = $em->find(Book::class, $id)) {
                    throw new NotFoundHttpException();
                }
            } else {
                $book = new Book();
            }
            if ($title = $request->request->getString('title')) {
                $book->title = $title;
            }
            if ($request->request->has('remove')) {
                $em->remove($book);
            } else {
                $em->persist($book);
            }

            $em->flush();
        }

        return $this->render('ux_turbo/broadcast/books.html.twig');
    }

    #[Route('/broadcast/artists', name: 'broadcast_artists', methods: ['GET', 'POST'])]
    public function broadcastArtists(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->request->get('id')) {
                if (!$artist = $em->find(Artist::class, $id)) {
                    throw new NotFoundHttpException();
                }
            } else {
                $artist = new Artist();
            }
            if ($name = $request->request->getString('name')) {
                $artist->name = $name;
            }
            if ($request->request->has('remove')) {
                $em->remove($artist);
            } else {
                $em->persist($artist);
            }

            $em->flush();
        }

        return $this->render('ux_turbo/broadcast/artists.html.twig');
    }

    #[Route('/broadcast/artists/{id}', name: 'broadcast_artist', requirements: ['id' => '\d+'], defaults: ['example' => 'Turbo Broadcast — Artists & Songs'], methods: ['GET'])]
    public function broadcastArtist(int $id, EntityManagerInterface $em): Response
    {
        if (!$artist = $em->find(Artist::class, $id)) {
            throw new NotFoundHttpException();
        }

        return $this->render('ux_turbo/broadcast/artist.html.twig', ['artist' => $artist]);
    }

    #[Route('/broadcast/songs', name: 'broadcast_songs', defaults: ['example' => 'Turbo Broadcast — Artists & Songs'], methods: ['GET', 'POST'])]
    public function broadcastSongs(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->request->get('id')) {
                if (!$song = $em->find(Song::class, $id)) {
                    throw new NotFoundHttpException();
                }
            } else {
                $song = new Song();
            }
            if ($title = $request->request->getString('title')) {
                $song->title = $title;
                $artistId = $request->request->get('artistId');
                if ($artistId > 0) {
                    $song->artist = $em->find(Artist::class, $artistId);
                }
            }
            if ($request->request->has('remove')) {
                $em->remove($song);
            } else {
                $em->persist($song);
            }

            $em->flush();
        }

        return $this->render('ux_turbo/broadcast/songs.html.twig');
    }

    #[Route('/broadcast/artist-from-song', name: 'broadcast_artist_from_song', methods: ['GET', 'POST'])]
    public function broadcastArtistFromSong(Request $request, EntityManagerInterface $em): Response
    {
        $song = null;
        if ($request->isMethod('POST')) {
            $id = $request->request->get('id');
            if (!$id) {
                $artist = new Artist();
                $artist->name = 'testing artist';

                $song = new Song();
                $song->artist = $artist;
                $song->title = 'testing song title';

                $em->persist($artist);
                $em->persist($song);
                $em->flush();
            } else {
                $song = $em->find(Song::class, $id);
                if (!$song) {
                    throw new NotFoundHttpException();
                }
                $artist = $song->artist;
                if (!$artist) {
                    throw new NotFoundHttpException();
                }
                $artist->name .= ' after change';

                $em->flush();
            }
        }

        return $this->render('ux_turbo/broadcast/artist_from_song.html.twig', [
            'song' => $song,
        ]);
    }
}
