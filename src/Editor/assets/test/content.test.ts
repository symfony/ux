import { describe, it, expect } from 'vitest';
import { HtmlContent } from '../src/content/HtmlContent.js';
import { BlockContent } from '../src/content/BlockContent.js';
import { PageContent } from '../src/content/PageContent.js';
import { EditorContentFormat } from '../src/content/EditorContent.js';

describe('content mirrors', () => {
  it('HtmlContent', () => {
    const c = HtmlContent.from('<p>x</p>', { bridgeId: 'ck' });
    expect(c.format).toBe(EditorContentFormat.Html);
    expect(c.getRaw()).toBe('<p>x</p>');
    expect(c.metadata.bridgeId).toBe('ck');
  });

  it('BlockContent', () => {
    const c = BlockContent.from({ version: '2.0', blocks: [{ type: 'p', data: {} }] });
    expect(c.format).toBe(EditorContentFormat.Blocks);
    expect(c.schemaVersion).toBe('2.0');
    expect(c.blocks.length).toBe(1);
  });

  it('PageContent', () => {
    const c = PageContent.from({ html: '<h1>x</h1>', css: 'h1{}', assets: [], components: [] });
    expect(c.format).toBe(EditorContentFormat.Page);
    expect(c.html).toBe('<h1>x</h1>');
  });
});
