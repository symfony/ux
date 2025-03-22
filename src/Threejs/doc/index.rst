Symfony UX Three Js
===================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Three JS is a Symfony bundle integrating interactive [three.Js](https://threejs.org) library in Symfony applications. It is part of `the Symfony UX initiative`_.

The package try to follow the structure of Three Js objects, unlike it not handle all the complexity of the javascript library. If you want more information about how to use three.js, you can start with this [manual page](https://threejs.org/manual) 

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-threejs

Create a Three JS scene
-----------------------

First create a new instance by calling ``new Three()``. 

- A ThreeJs object is made of a renderer which contains a `Scene` and an array `Camera`.
- A `Scene` has a `Material`, an array of `Light`, an array of `Mesh` (geometrical objects) and an array of `Model` (loaded 3D models)

Start by creating a new threejs instance::

    use Symfony\UX\ThreeJs\Three;

    // Create a new threejs instance
    $three = new Three(int $width, int $height);

Default width and height is 300px.

Add camera
~~~~~~~~~~

Threejs instance has a default camera but you can add new one with
    
    $three->addCamera(Camera $camera);

You can several type of camera, like `PerspectiveCamera` or `OrthographicCamera` which extends abstract `Camera` class.

Custom the scene
~~~~~~~~~~~~~~~~

A scene can have a background material (e.g color, texture).

    $three = new Three();
    $three->getScene()->setMaterial(
        new MeshBasic('green'),
    );


Add a light to the scene
~~~~~~~~~~~~~~~~~~~~~~~~

Lights are related to `Scene` object. You can use the `Three::addLight(Light $light)` method to directly add a new light to the scene.

You can add different type of lights (e.g. `AmbientLight`, `DirectionalLight`) which extends abstract `Light` class

    $three = new Three();
    $three->addLight(
        new AmbientLight(color: 'blue', intensity: 3)
    );

    $three->addLight(
        new DirectionalLight(
            color: 'white',
            intensity: 10,
            position: new Vector3(x:1, y: 0, z: 1),
            target: new Vector3(x: 1, y: 1, z: 0);
        )
    );

`Vector3` is a generic class to manage x,y,z points. It is used in several classes.

Add a Mesh to the scene
~~~~~~~~~~~~~~~~~~~~~~~

`Mesh` is a special 3D object which combines a geometrical shape with a material.

- Several gometrical shape are available, with extends abstract `BufferGeometry` class, e.g : `Box`, `Sphere`, `Plane`, `Cylinder`...

- Several Material are available (wich extends abstract `Material` class) e.g : `MeshBasic`, `MeshPhong`. Each type of material has a color or texture, and has specific surface properties (transparency, reflection...).

    $three = new Three();
    $three->addMesh(
        new Mesh(
            geometry: new Box(width: 1, height: 1, depth: 2),
            material: new MeshBasic(color: 'green', opacity: 0.8),
        )
    );

    $three->addMesh(
        new Mesh(
            geometry: new Sphere(radius: 2),
            material: new MeshPhong(map: 'path/to/texture.png'),
        )
    );

Meshes have inherited methods `setAngle(Vector3 $angle)` and `setPosition(Vector3 $position)` to change mesh initial position and angle in the scene. 

    $mesh->setPosition(x: 0, y: 0, z: 0);
    $mesh->setAngle(aX: 0, aY: 0, aZ: 0);

Animate a Mesh
~~~~~~~~~~~~~~

A mesh can be animated with translation, scale or rotation. `Mesh` object has an `animation` property which contains an `Animation` object.
   
    new Mesh(
        geometry: new Box(width: 1, height: 1, depth: 2),
        material: new MeshBasic(color: 'green', opacity: 0.8),
        animation: (new Animation())->rotate(rY: 0.01)),
    );

Load a 3D model
~~~~~~~~~~~~~~~

Even if you can create mesh and add them to a scene, you will mostly need to load existing complex 3D models. These models can be added to scene with function `Three::addModel(Model $model)`. Actual availabel model loader is `GLTFModel  ` which extends abstract `Model` class/

    $three = new Three();
    $three->addModel(
        new GLTFModel(
            path: '/path/to/model.glb'
        )
    );

You can also animate a model with rotation, translation or scale like a `Mesh`, bug loaded models can also embbed their own complex animations (also called clip). Each animation has a name and you can play a model animation using

    new GLTFModel(
        path: '/path/to/model.glb'
        animation: new Animation(
            playClip: 'clip_name',
        )
    )

Render a three Js scene
-----------------------

To render a map in your Twig template, use the ``render_threejs`` Twig function, which takes a `Three` object as parameter , e.g.:

To be visible, the map must have a defined height:

.. code-block:: twig

    {{ render_threejs(my_three_js }}
