<?php

namespace App\Controllers;

use App\Models\Buildings;
use App\Models\Rooms;
use \Core\View;

class Building extends \Core\Controller
{
    public function indexAction()
    {
        $buildings = Buildings::getAll();
        $roomsByBuilding = Rooms::getMapByBuilding();

        View::renderTemplate('Building/index.html', [
            'buildings' => $buildings,
            'roomsByBuilding' => $roomsByBuilding,
        ]);
    }
}
