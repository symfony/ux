import {
    htmlToElement,
} from '../src/dom_utils';
import { DiffDOM } from "diff-dom"

describe('diff-dom', () => {
    it('Just playing', () => {


        const dd = new DiffDOM();

        const from = htmlToElement(`
        <div>
            Text at the top
            <div class="child-element">Child1 text</div>
            <div>between</div>
            <div class="child-element">Child2 text<span class="foo">icon</span></div>
        </div>
        `)

        const to = htmlToElement(`
        <div>
            Text at the top
            <div class="child-element">Child1 text</div>
            <div>between</div>
            <div class="child-element">Child2 text</div>
        </div>
        `)

        const diff = dd.diff(from, to)
        console.log(diff);
    });
});
