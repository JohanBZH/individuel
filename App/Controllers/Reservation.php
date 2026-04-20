<?php

namespace App\Controllers;

use App\Models\Reservations;
use App\Models\Buildings;
use App\Models\Rooms;
use \Core\View;

class Reservation extends \Core\Controller
{
    // Show add form / handle POST
    public function indexAction()
    {
        if (!isset($_SESSION['user'])) {
            throw new \Exception('You must be logged in');
        }

        $buildings = Buildings::getAll();
        $roomsMap = Rooms::getMapByBuilding();

        if (isset($_POST['submit'])) {
            $f = $_POST;

            // basic validation
            $f['user_id'] = $_SESSION['user']['id'];

            // expected inputs now: building_id, room_id, start_datetime, end_datetime, comment
            $buildingId = isset($f['building_id']) && $f['building_id'] !== '' ? (int)$f['building_id'] : null;
            $roomId = isset($f['room_id']) && $f['room_id'] !== '' ? (int)$f['room_id'] : null;
            $start = trim($f['start_datetime']);
            $end = trim($f['end_datetime']);

            // normalize datetime-local (browser) value `YYYY-MM-DDTHH:MM` -> `YYYY-MM-DD HH:MM:00`
            $start = str_replace('T', ' ', $start);
            $end = str_replace('T', ' ', $end);
            if (strlen($start) === 16) { $start .= ':00'; }
            if (strlen($end) === 16) { $end .= ':00'; }

            // reflect normalized values back to data array (so form retains correct display on error)
            $f['start_datetime'] = $start;
            $f['end_datetime'] = $end;

            if (empty($roomId) || empty($start) || empty($end)) {
                $error = 'Veuillez sélectionner une salle, et renseigner la date de début et la date de fin.';
                View::renderTemplate('Reservation/Add.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            // enforce same calendar date for end as start
            try {
                $dtStart = new \DateTime($start);
                $dtEnd = new \DateTime($end);
                // Force end date to carry the same Y-m-d as start
                $end = $dtStart->format('Y-m-d') . ' ' . $dtEnd->format('H:i:s');
                $f['end_datetime'] = $end;
                
                // Also format start exactly to SQL datetime format
                $start = $dtStart->format('Y-m-d H:i:s');
                $f['start_datetime'] = $start;
            } catch (\Exception $e) { /* handled by validation below */ }
            try {
                $dtStart = new \DateTime($start);
                $dtEnd = new \DateTime($end);
                if ($dtStart >= $dtEnd) {
                    $error = 'La date de début doit être antérieure à la date de fin.';
                    View::renderTemplate('Reservation/Add.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                    return;
                }
            } catch (\Exception $e) {
                $error = 'Format de date invalide.';
                View::renderTemplate('Reservation/Add.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            // conflict check
            if (Reservations::isConflicting($roomId, $start, $end)) {
                $error = 'Conflit : cette salle est déjà réservée pour cet intervalle.';
                View::renderTemplate('Reservation/Add.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            try {
                // Persist room_id; also include human-readable room label for legacy display
                $f['room_id'] = $roomId;
                if (!isset($f['room']) || $f['room'] === '') {
                    $f['room'] = Rooms::findNameById($roomId) ?? '';
                }
                $id = Reservations::save($f);
                header('Location: /reservation/' . $id);
            } catch (\Exception $e) {
                View::renderTemplate('Reservation/Add.html', ['error' => $e->getMessage(), 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
            }
            return;
        }

        View::renderTemplate('Reservation/Add.html', [
            'buildings' => $buildings,
            'roomsMap' => $roomsMap
        ]);
    }

    // show one reservation
    public function showAction()
    {
        $id = $this->route_params['id'];

        try {
            $reservation = Reservations::getOne($id);
        } catch (\Exception $e) {
            var_dump($e);
        }

        View::renderTemplate('Reservation/Show.html', [
            'reservation' => $reservation[0]
        ]);
    }

    // edit a reservation (owner or admin)
    public function editAction()
    {
        if (!isset($_SESSION['user'])) {
            throw new \Exception('You must be logged in');
        }

        $id = (int)$this->route_params['id'];
        $res = Reservations::getOne($id);
        if (empty($res)) { throw new \Exception('Réservation introuvable'); }
        $res = $res[0];

        $user = $_SESSION['user'];
        $isOwner = ($res['user_id'] == $user['id']);
        $isAdmin = isset($user['is_admin']) && $user['is_admin'];
        if (!$isOwner && !$isAdmin) {
            throw new \Exception('Vous ne pouvez pas modifier cette réservation.');
        }

        $buildings = Buildings::getAll();
        $roomsMap = Rooms::getMapByBuilding();

        if (isset($_POST['submit'])) {
            $f = $_POST;
            // preserve owner
            $f['user_id'] = $res['user_id'];

            $buildingId = isset($f['building_id']) && $f['building_id'] !== '' ? (int)$f['building_id'] : null;
            $roomId = isset($f['room_id']) && $f['room_id'] !== '' ? (int)$f['room_id'] : null;
            $start = str_replace('T', ' ', trim($f['start_datetime']));
            $end = str_replace('T', ' ', trim($f['end_datetime']));
            if (strlen($start) === 16) { $start .= ':00'; }
            if (strlen($end) === 16) { $end .= ':00'; }
            $f['start_datetime'] = $start;
            $f['end_datetime'] = $end;

            if (empty($roomId) || empty($start) || empty($end)) {
                $error = 'Veuillez sélectionner une salle, et renseigner la date de début et la date de fin.';
                View::renderTemplate('Reservation/Edit.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            try {
                $dtStartTmp = new \DateTime($start);
                $dtEndTmp = new \DateTime($end);
                $end = $dtStartTmp->format('Y-m-d') . ' ' . $dtEndTmp->format('H:i:s');
                $f['end_datetime'] = $end;

                $start = $dtStartTmp->format('Y-m-d H:i:s');
                $f['start_datetime'] = $start;
            } catch (\Exception $e) { }

            try {
                $dtStart = new \DateTime($start);
                $dtEnd = new \DateTime($end);
                if ($dtStart >= $dtEnd) {
                    $error = 'La date de début doit être antérieure à la date de fin.';
                    View::renderTemplate('Reservation/Edit.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                    return;
                }
            } catch (\Exception $e) {
                $error = 'Format de date invalide.';
                View::renderTemplate('Reservation/Edit.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            if (Reservations::isConflictingExcept($roomId, $start, $end, $id)) {
                $error = 'Conflit : cette salle est déjà réservée pour cet intervalle.';
                View::renderTemplate('Reservation/Edit.html', ['error' => $error, 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
                return;
            }

            try {
                $f['room_id'] = $roomId;
                if (!isset($f['room']) || $f['room'] === '') {
                    $f['room'] = Rooms::findNameById($roomId) ?? '';
                }
                Reservations::update($id, $f);
                header('Location: /reservation/' . $id);
            } catch (\Exception $e) {
                View::renderTemplate('Reservation/Edit.html', ['error' => $e->getMessage(), 'data' => $f, 'buildings' => $buildings, 'roomsMap' => $roomsMap]);
            }
            return;
        }

        // Prefill data from existing reservation
        $data = [
            'building_id' => $res['building_id'] ?? '',
            'room_id' => $res['room_id'] ?? '',
            'start_datetime' => $res['start_datetime'] ?? '',
            'end_datetime' => $res['end_datetime'] ?? '',
            'comment' => $res['comment'] ?? ''
        ];

        View::renderTemplate('Reservation/Edit.html', [
            'id' => $id,
            'data' => $data,
            'buildings' => $buildings,
            'roomsMap' => $roomsMap
        ]);
    }

    // list logged user's reservations
    public function myAction()
    {
        if (!isset($_SESSION['user'])) {
            throw new \Exception('You must be logged in');
        }

    $id = $_SESSION['user']['id'];
    $view = isset($_GET['view']) && $_GET['view'] === 'liste' ? 'liste' : 'planning';

    // Pagination for the user's list (bottom list)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $list = Reservations::getByUserPaged($id, $perPage, $offset);
    $total = Reservations::countByUser($id);
    $pages = (int)ceil($total / $perPage);

        // Monthly calendar context
        $monthParam = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');
        $year = (int)substr($monthParam, 0, 4);
        $month = (int)substr($monthParam, 5, 2);
        $first = new \DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $daysInMonth = (int)$first->format('t');
        $startOfMonth = $first->format('Y-m-d 00:00:00');
        $endOfMonth = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $daysInMonth);
        $firstWeekday = (int)$first->format('N'); // 1=Mon .. 7=Sun

        // prev/next
        $prev = (clone $first)->modify('-1 month');
        $next = (clone $first)->modify('+1 month');
        $prevParam = $prev->format('Y-m');
        $nextParam = $next->format('Y-m');

    // French month label
        $months = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
        $monthLabel = ucfirst($months[$month]) . ' ' . $year;

        // Fetch reservations within month
        $monthReservations = Reservations::getByUserInRange($id, $startOfMonth, $endOfMonth);
        $byDay = [];
        foreach ($monthReservations as $r) {
            // group by day of month from start_datetime
            try {
                $d = new \DateTime($r['start_datetime']);
                $day = (int)$d->format('j');
            } catch (\Exception $e) {
                $day = (int)substr($r['start_datetime'], 8, 2);
            }
            if (!isset($byDay[$day])) $byDay[$day] = [];
            $byDay[$day][] = $r;
        }

        $calendar = [
            'param' => $monthParam,
            'year' => $year,
            'month' => $month,
            'label' => $monthLabel,
            'daysInMonth' => $daysInMonth,
            'firstWeekday' => $firstWeekday,
            'prev' => $prevParam,
            'next' => $nextParam,
            'byDay' => $byDay,
            'today' => (new \DateTime())->format('Y-m-d')
        ];

        View::renderTemplate('Reservation/My.html', [
            'list' => $list,
            'calendar' => $calendar,
            'view' => $view,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => $pages
            ]
        ]);
    }

    // cancel a reservation (owner or admin)
    public function cancelAction()
    {
        if (!isset($_SESSION['user'])) {
            throw new \Exception('You must be logged in');
        }

        $id = $this->route_params['id'];

        // fetch reservation to check ownership
        $res = Reservations::getOne($id);
        if (empty($res)) {
            $_SESSION['flash_message'] = 'Réservation introuvable.';
            header('Location: /myreservations');
            return;
        }

        $res = $res[0];

        $user = $_SESSION['user'];
        $isOwner = ($res['user_id'] == $user['id']);
        $isAdmin = isset($user['is_admin']) && $user['is_admin'];

        if (!$isOwner && !$isAdmin) {
            $_SESSION['flash_message'] = "Accès non autorisé, contactez l'administrateur.rice";
            header('Location: /');
            return;
        }

        try {
            Reservations::delete($id);
            $_SESSION['flash_message'] = 'Réservation annulée.';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Erreur lors de l\'annulation: ' . $e->getMessage();
        }

        header('Location: /myreservations');
    }

    // admin listing of all reservations
    public function adminAction()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
            throw new \Exception('You must be an admin');
        }

        $list = Reservations::getAll('');
        View::renderTemplate('Reservation/Admin.html', ['list' => $list]);
    }
}
