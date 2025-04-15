import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

export type Material = {
    color: string;
    opacity: number;
    map: string;
    transparent: boolean;
    type: string;
    doubleSide: boolean;
    skybox: boolean;
}

export type Mesh = {
    geometry: any;
    material: Material;
    animation: any;
}

export type Light = {
    type: String;
    color: THREE.Color;
    intensity: number;
    position: THREE.Vector3,
    target: THREE.Vector3,
}

export type Camera = {
    type: String;
    position: THREE.Vector3,
    near: number;
    far: number;
    aspect: number;
    fov: number;
    top: number;
    left: number;
    right: number;
    bottom: number;
}

export default class extends Controller {
    declare threeValue: any;

    static values = {
        three: Object,
    }
    private renderer: THREE.WebGLRenderer | null = null;

    connect() {
        this.dispatchEvent('pre-connect', {
            options: this.threeValue,
        });

        const threeValue = this.threeValue;
        this.renderer = new THREE.WebGLRenderer();
        
        this.createScene(threeValue);
    }
    

    createScene(data: any) {
        if(this.renderer === null) {
            return;
        }
        /** init renderer */
        const rendererValue = data.renderer;
        this.renderer?.setSize(rendererValue.width ?? window.innerWidth, rendererValue.height ?? window.innerHeight);
        this.element.appendChild(this.renderer.domElement);
        // /** init scene */
        const sceneValue = rendererValue.scene;
        let scene = new THREE.Scene();
        const light = new THREE.AmbientLight(0x404040);
        scene.add(light);

        if (sceneValue.material.color) {
            scene.background = new THREE.Color(sceneValue.material.color);
        }
        if (sceneValue.material.map) {
            const texture = new THREE.TextureLoader().load(sceneValue.material.map);
            if (sceneValue.material.skybox)
                texture.mapping = THREE.EquirectangularReflectionMapping;
            scene.background = texture;

        }


        /** cameras */
        const cameras: THREE.Camera[] = [];
        for (let cameraData of rendererValue.cameras) {
            cameras.push(this.createCamera(cameraData, this.renderer));
        }

        /** lights */
        for (let lightData of sceneValue.lights) {
            this.createLight(lightData, scene);
        }

        /** controls */
        if (rendererValue.controls) {
            this.setControls(cameras[0], this.renderer);
        }

        /** load 3d models */
        for (let modelData of this.threeValue.renderer.scene.models) {
            this.createModel(modelData, scene);
        }

        /** load meshes */
        let animatedObjects = [];
        for (let mesh of sceneValue.meshes) {
            animatedObjects.push({ mesh: this.createMesh(mesh, scene), animation: mesh.animation });
        }

        /** animation */
        const animate = () => {
            for (let animationObject of animatedObjects) {
                const { mesh, animation } = animationObject;
                mesh.rotation.x += animation.rotation.x;
                mesh.rotation.y += animation.rotation.y;
                mesh.rotation.z += animation.rotation.z;
                mesh.scale.x += animation.scale.x;
                mesh.scale.y += animation.scale.y;
                mesh.scale.z += animation.scale.z;
                mesh.position.x += animation.translation.x;
                mesh.position.y += animation.translation.y;
                mesh.position.z = animation.translation.z;
            }
            this.renderer?.render(scene, cameras[0]);

            requestAnimationFrame(animate);
        };

        animate();

        this.dispatchEvent('connect', {
            renderer: this.renderer,
            scene: scene,
        });
    }

    transform(object3D: THREE.Object3D, transformationData: any): any {
        const { position, angle } = transformationData;
        object3D.translateX(position.x);
        object3D.translateY(position.y);
        object3D.translateZ(position.z);
        object3D.setRotationFromEuler(new THREE.Euler().setFromVector3(angle));
    }

    createMesh(meshData: Mesh, scene: THREE.Scene): THREE.Mesh {
        let mesh = new THREE.Mesh(
            this.createGeometry(meshData.geometry),
            this.createMaterial(meshData.material),
        );

        this.transform(mesh, meshData)

        scene.add(mesh);

        return mesh;
    }

    createGeometry(geometryData: any): THREE.BufferGeometry {

        if (geometryData.type == 'Sphere') {
            const { radius, widthSegments, heightSegments } = geometryData;
            return new THREE.SphereGeometry(radius, widthSegments, heightSegments);
        }
        if (geometryData.type == 'Plane') {
            const { width, height, widthSegments, heightSegments } = geometryData;
            return new THREE.PlaneGeometry(width, height, widthSegments, heightSegments);
        }
        if (geometryData.type == 'Cylinder') {
            const { radiusTop, radiusBottom, height, radialSegments, heightSegments, openEnded, thetaStart, thetaLength } = geometryData;
            return new THREE.CylinderGeometry(radiusTop, radiusBottom, height, radialSegments, heightSegments, openEnded, thetaStart, thetaLength);
        }

        const { width, height, depth } = geometryData;
        return new THREE.BoxGeometry(width, height, depth);
    }

    createMaterial(materialData: Material): THREE.Material | undefined {
        const { color, opacity, transparent, map, doubleSide } = materialData;
        let texture = null;

        let material;

        if (materialData.type == 'MeshPhong') {
            material = new THREE.MeshPhongMaterial({ color, opacity, transparent });
        } else if (materialData.type == 'MeshBasic') {
            material = new THREE.MeshBasicMaterial({ color, opacity, transparent });
        } else {
            return undefined;
        }

        if (map) {
            texture = new THREE.TextureLoader().load(materialData.map);
            texture.colorSpace = THREE.SRGBColorSpace;
            material.map = texture;
        }

        if (doubleSide) {
            material.side = THREE.DoubleSide;
        }

        return material;
    }

    createLight(lightData: Light, scene: THREE.Scene) {
        if (lightData.type == 'Ambient') {
            const { color, intensity } = lightData;
            const light = new THREE.AmbientLight(color, intensity);
            scene.add(light);
        }
        if (lightData.type == 'Directional') {
            const { color, intensity, position, target } = lightData;
            const light = new THREE.DirectionalLight(color, intensity);
            light.position.set(position.x, position.y, position.z);
            light.target.position.set(target.x, target.y, target.z);
            scene.add(light.target);
            scene.add(light);
        }
    }

    createCamera(cameraData: Camera, renderer: THREE.WebGLRenderer): THREE.Camera {
        let camera: THREE.Camera;

        if (cameraData.type == 'Perspective') {
            const { fov, near, far } = cameraData;
            camera = new THREE.PerspectiveCamera(fov, (renderer.domElement.clientWidth / renderer.domElement.clientHeight), near, far);
        } else {
            const { left, right, top, bottom, near, far } = cameraData;
            camera = new THREE.OrthographicCamera(left, right, top, bottom, near, far);
        }

        camera.position.set(cameraData.position.x, cameraData.position.y, cameraData.position.z)
        return camera;
    }

    setControls(controlCamera: THREE.Camera, renderer: THREE.WebGLRenderer): void {
        const controls = new OrbitControls(controlCamera, renderer.domElement);
        controls.listenToKeyEvents(window);
        controls.update();
    }

    createModel(modelData: any, scene: THREE.Scene): any {
        const { path, animation } = modelData;
        let loader = new GLTFLoader();

        loader.load(path, (model) => {
            this.transform(model.scene, modelData);
            scene.add(model.scene);

            model.scene.traverse(function(object) {
                if (object instanceof THREE.Mesh) {
                   // const material = object.material; 
                    // if(material.isMaterial && material.type=='MeshStandardMaterial') {
                    //     material.dispose();
                    //     object.material = new THREE.MeshBasicMaterial();
                    //     object.material.color = 'green';
                    // }
                }
                //     // Supposons que vous voulez changer la texture du premier matériau trouvé
                //     const material = child.material;
        
                //     // Charger une nouvelle texture
                //     const textureLoader = new THREE.TextureLoader();
                //     textureLoader.load('path/to/your/new-texture.jpg', function(texture) {
                //         // Mettre à jour la texture du matériau
                //         material.map = texture;
                //         material.needsUpdate = true; // Indiquer que le matériau doit être mis à jour
                //     });
                // }
            });

            const mixer = new THREE.AnimationMixer(model.scene);
            const clock = new THREE.Clock();
            if (animation.playClip) {
                const clip = model.animations.find(a => a.name === animation.playClip);
                if (clip) {
                    const runAction = mixer.clipAction(clip);
                    runAction.play();
                }
            }

            function animate() {
                model.scene.rotation.x += animation.rotation.x;
                model.scene.rotation.y += animation.rotation.y;
                model.scene.rotation.z += animation.rotation.z;
                model.scene.translateX(animation.translation.x);
                model.scene.translateY(animation.translation.y);
                model.scene.translateZ(animation.translation.z);
                if (animation.playClip) {
                    const delta = clock.getDelta();
                    mixer.update(delta);
                }

                requestAnimationFrame(animate);
            }
            animate();
        });
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'ux:threejs' });
    }

    threeValueChanged(): void {
        const threeValue = this.threeValue;

        this.createScene(threeValue);

    }
}

