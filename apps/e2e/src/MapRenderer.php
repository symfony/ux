<?php

namespace App;

enum MapRenderer: string
{
    case Leaflet = 'leaflet';
    case Google = 'google';
}
