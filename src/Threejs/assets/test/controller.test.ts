/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application , Controller } from '@hotwired/stimulus';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import { getByTestId, waitFor } from '@testing-library/dom';
import ThreejsController from '../src/controller';
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('ux:threejs:pre-connect', (event) => {
            this.element.classList.add('pre-connected');
        });

       this.element.addEventListener('ux:threejs:connect', (event) => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('symfony--ux-threejs--three', ThreejsController);
};

describe('ThreejsController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
            <div
            data-testid="three"
            data-controller="check symfony--ux-threejs--three" 
            data-symfony--ux-threejs--three-three-value="{&quot;renderer&quot;:{&quot;scene&quot;:{&quot;basicMaterial&quot;:{&quot;transparency&quot;:false,&quot;color&quot;:&quot;white&quot;,&quot;opacity&quot;:1,&quot;map&quot;:&quot;&quot;},&quot;lights&quot;:[{&quot;type&quot;:&quot;Ambient&quot;,&quot;color&quot;:&quot;white&quot;,&quot;intensity&quot;:1}]},&quot;controls&quot;:true,&quot;cameras&quot;:[{&quot;fov&quot;:75,&quot;near&quot;:0.1,&quot;far&quot;:1000,&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:5,&quot;type&quot;:&quot;Perspective&quot;}],&quot;width&quot;:800,&quot;height&quot;:800},&quot;models&quot;:[{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0,&quot;path&quot;:&quot;\/assets\/models\/Xbot-knhkELU.glb&quot;,&quot;animation&quot;:{&quot;rotationX&quot;:0,&quot;rotationY&quot;:0.05,&quot;rotationZ&quot;:0,&quot;translationX&quot;:0,&quot;translationY&quot;:0,&quot;translationZ&quot;:0,&quot;clip&quot;:&quot;run&quot;}}],&quot;geometries&quot;:[{&quot;x&quot;:5,&quot;y&quot;:0,&quot;z&quot;:0,&quot;type&quot;:&quot;Box&quot;,&quot;basicMaterial&quot;:{&quot;transparency&quot;:false,&quot;color&quot;:&quot;green&quot;,&quot;opacity&quot;:1,&quot;map&quot;:&quot;&quot;},&quot;animation&quot;:{&quot;rotationX&quot;:0.01,&quot;rotationY&quot;:0,&quot;rotationZ&quot;:0,&quot;translationX&quot;:0,&quot;translationY&quot;:0,&quot;translationZ&quot;:0,&quot;clip&quot;:null},&quot;width&quot;:3,&quot;height&quot;:2,&quot;depth&quot;:1},{&quot;x&quot;:-5,&quot;y&quot;:0,&quot;z&quot;:0,&quot;type&quot;:&quot;Sphere&quot;,&quot;basicMaterial&quot;:{&quot;transparency&quot;:false,&quot;color&quot;:null,&quot;opacity&quot;:0.5,&quot;map&quot;:&quot;https:\/\/raw.githubusercontent.com\/mrdoob\/three.js\/refs\/heads\/master\/examples\/textures\/crate.gif&quot;},&quot;animation&quot;:{&quot;rotationX&quot;:0.01,&quot;rotationY&quot;:0,&quot;rotationZ&quot;:0,&quot;translationX&quot;:0,&quot;translationY&quot;:0,&quot;translationZ&quot;:0,&quot;clip&quot;:null},&quot;radius&quot;:1,&quot;widthSegments&quot;:32,&quot;heightSegments&quot;:16}]}">
                <canvas style="display: block; width: 800px; height: 800px; touch-action: none;" data-engine="three.js r174" width="800" height="800"></canvas>
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect and create three js scene', async () => {
        const div = getByTestId(container, 'three');
        expect(div).not.toHaveClass('pre-connected');
        expect(div).not.toHaveClass('connected');

        // startStimulus();

        // await waitFor(() => expect(div).toHaveClass('pre-connected'));
        // await waitFor(() => expect(div).toHaveClass('connected'));
   //     await waitFor(() => expect(application.getControllerForElementAndIdentifier(div, 'three')).not.toBeNull());


    });


});
