import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

class default_1 extends Controller {
    connect() {
        this.dispatchEvent('pre-connect', {
            options: this.threeValue,
        });
        const renderer = new THREE.WebGLRenderer();
        const rendererValue = this.threeValue.renderer;
        renderer.setSize(rendererValue.width ?? window.innerWidth, rendererValue.height ?? window.innerHeight);
        this.element.appendChild(renderer.domElement);
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
        const cameras = [];
        for (let cameraData of rendererValue.cameras) {
            cameras.push(this.createCamera(cameraData, renderer));
        }
        for (let lightData of sceneValue.lights) {
            this.createLight(lightData, scene);
        }
        if (rendererValue.controls) {
            this.setControls(cameras[0], renderer);
        }
        for (let modelData of this.threeValue.renderer.scene.models) {
            console.log(modelData);
            this.createModel(modelData, scene);
        }
        let animatedObjects = [];
        for (let mesh of sceneValue.meshes) {
            animatedObjects.push({ mesh: this.createMesh(mesh, scene), animation: mesh.animation });
        }
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
            renderer.render(scene, cameras[0]);
            requestAnimationFrame(animate);
        };
        animate();
        this.dispatchEvent('connect', {
            renderer: renderer,
            scene: scene,
        });
    }
    transform(object3D, transformationData) {
        const { position, angle } = transformationData;
        object3D.translateX(position.x);
        object3D.translateY(position.y);
        object3D.translateZ(position.z);
        object3D.setRotationFromEuler(new THREE.Euler().setFromVector3(angle));
    }
    createMesh(meshData, scene) {
        let mesh = new THREE.Mesh(this.createGeometry(meshData.geometry), this.createMaterial(meshData.material));
        this.transform(mesh, meshData);
        scene.add(mesh);
        return mesh;
    }
    createGeometry(geometryData) {
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
    createMaterial(materialData) {
        const { color, opacity, transparent, map, doubleSide } = materialData;
        let texture = null;
        let material;
        if (materialData.type == 'MeshPhong') {
            material = new THREE.MeshPhongMaterial({ color, opacity, transparent });
        }
        else if (materialData.type == 'MeshBasic') {
            material = new THREE.MeshBasicMaterial({ color, opacity, transparent });
        }
        else {
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
    createLight(lightData, scene) {
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
    createCamera(cameraData, renderer) {
        let camera;
        if (cameraData.type == 'Perspective') {
            const { fov, near, far } = cameraData;
            camera = new THREE.PerspectiveCamera(fov, (renderer.domElement.clientWidth / renderer.domElement.clientHeight), near, far);
        }
        else {
            const { left, right, top, bottom, near, far } = cameraData;
            camera = new THREE.OrthographicCamera(left, right, top, bottom, near, far);
        }
        camera.position.set(cameraData.position.x, cameraData.position.y, cameraData.position.z);
        return camera;
    }
    setControls(controlCamera, renderer) {
        const controls = new OrbitControls(controlCamera, renderer.domElement);
        controls.listenToKeyEvents(window);
        controls.update();
    }
    createModel(modelData, scene) {
        const { path, animation } = modelData;
        let loader = new GLTFLoader();
        loader.load(path, (model) => {
            this.transform(model.scene, modelData);
            scene.add(model.scene);
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
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'ux:threejs' });
    }
}
default_1.values = {
    three: Object,
};

export { default_1 as default };
