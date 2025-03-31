import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
export type Material = {
    color: string;
    opacity: number;
    map: string;
    transparent: boolean;
    type: string;
    doubleSide: boolean;
    skybox: boolean;
};
export type Mesh = {
    geometry: any;
    material: Material;
    animation: any;
};
export type Light = {
    type: String;
    color: THREE.Color;
    intensity: number;
    position: THREE.Vector3;
    target: THREE.Vector3;
};
export type Camera = {
    type: String;
    position: THREE.Vector3;
    near: number;
    far: number;
    aspect: number;
    fov: number;
    top: number;
    left: number;
    right: number;
    bottom: number;
};
export default class extends Controller {
    threeValue: any;
    static values: {
        three: ObjectConstructor;
    };
    connect(): void;
    transform(object3D: THREE.Object3D, transformationData: any): any;
    createMesh(meshData: Mesh, scene: THREE.Scene): THREE.Mesh;
    createGeometry(geometryData: any): THREE.BufferGeometry;
    createMaterial(materialData: Material): THREE.Material | undefined;
    createLight(lightData: Light, scene: THREE.Scene): void;
    createCamera(cameraData: Camera, renderer: THREE.WebGLRenderer): THREE.Camera;
    setControls(controlCamera: THREE.Camera, renderer: THREE.WebGLRenderer): void;
    createModel(modelData: any, scene: THREE.Scene): any;
    private dispatchEvent;
}
