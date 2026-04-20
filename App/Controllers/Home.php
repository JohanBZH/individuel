<?php

namespace App\Controllers;

use App\Models\Reservations;
use App\Models\Buildings;
use App\Models\Rooms;
use \Core\View;
use Exception;

/**
 * Home controller
 */
class Home extends \Core\Controller
{

    /**
     * Affiche la page d'accueil
     *
     * @return void
     * @throws \Exception
     */
    public function indexAction()
    {
        // Pagination params
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Upcoming paged
        try {
            $reservations = Reservations::getUpcomingPaged($perPage, $offset);
            $total = Reservations::countUpcoming();
        } catch (Exception $e) {
            $reservations = [];
            $total = 0;
        }
        $pages = (int)ceil($total / $perPage);

        // Suggested: top buildings and rooms
        $topBuildings = Reservations::getTopBuildingsByReservations(3);
        $topRooms = Reservations::getTopRoomsByReservations(5);

        // Totals for CTA (full list on /buildings)
        try {
            $allBuildings = Buildings::getAll();
            $allRooms = Rooms::getAll();
            $totalBuildings = is_array($allBuildings) ? count($allBuildings) : 0;
            $totalRooms = is_array($allRooms) ? count($allRooms) : 0;
        } catch (Exception $e) {
            $totalBuildings = 0;
            $totalRooms = 0;
        }

        View::renderTemplate('Home/index.html', [
            'reservations' => $reservations,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => $pages
            ],
            'topBuildings' => $topBuildings,
            'topRooms' => $topRooms,
            'totalBuildings' => $totalBuildings,
            'totalRooms' => $totalRooms
        ]);
    }
}
