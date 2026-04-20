<?php

namespace Core;

/**
 * View
 *
 * PHP version 7.0
 */
class View
{

    /**
     * Render a view file
     *
     * @param string $view  The view file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function render($view, $args = [])
    {
        extract($args, EXTR_SKIP);

        $file = dirname(__DIR__) . "/App/Views/$view";  // relative to Core directory

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("$file not found");
        }
    }

    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function renderTemplate($template, $args = [])
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig\Loader\Filesystemloader(dirname(__DIR__) . '/App/Views');
            $twig = new \Twig\Environment($loader, ['debug' => true,]);
            $twig->addExtension(new \Twig\Extension\DebugExtension());

            // Add a French datetime formatter filter: {{ date|fr_datetime }}
            $twig->addFilter(new \Twig\TwigFilter('fr_datetime', function ($datetime) {
                if (!$datetime) return '';
                try {
                    $dt = new \DateTime($datetime);
                } catch (\Exception $e) {
                    return $datetime;
                }
                $days = [1=>'lundi',2=>'mardi',3=>'mercredi',4=>'jeudi',5=>'vendredi',6=>'samedi',7=>'dimanche'];
                $months = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
                $dow = (int)$dt->format('N');
                $dayName = isset($days[$dow]) ? $days[$dow] : $dt->format('l');
                $day = $dt->format('j');
                $mon = (int)$dt->format('n');
                $monthName = isset($months[$mon]) ? $months[$mon] : $dt->format('F');
                $year = $dt->format('Y');
                $time = $dt->format('H\hi');
                // Capitalize first letter of weekday; keep month lowercase per French typographic norms
                $dayName = mb_convert_case($dayName, MB_CASE_TITLE, 'UTF-8');
                return sprintf('%s %s %s %s %s', $dayName, $day, $monthName, $year, $time);
            }));

            // Add a duration formatter (minutes -> "1 h 30" or "45 min")
            $twig->addFilter(new \Twig\TwigFilter('fr_duration', function ($minutes) {
                if ($minutes === null || $minutes === '') return '';
                $m = (int)$minutes;
                if ($m <= 0) return '0 min';
                $h = intdiv($m, 60);
                $mn = $m % 60;
                if ($h > 0 && $mn > 0) {
                    return sprintf('%d h %d', $h, $mn);
                } elseif ($h > 0) {
                    return sprintf('%d h', $h);
                }
                return sprintf('%d min', $mn);
            }));

            // French date only: Lundi 15 novembre 2025
            $twig->addFilter(new \Twig\TwigFilter('fr_date', function ($datetime) {
                if (!$datetime) return '';
                try { $dt = new \DateTime($datetime); } catch (\Exception $e) { return $datetime; }
                $days = [1=>'lundi',2=>'mardi',3=>'mercredi',4=>'jeudi',5=>'vendredi',6=>'samedi',7=>'dimanche'];
                $months = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
                $dow = (int)$dt->format('N');
                $dayName = isset($days[$dow]) ? $days[$dow] : $dt->format('l');
                $day = $dt->format('j');
                $mon = (int)$dt->format('n');
                $monthName = isset($months[$mon]) ? $months[$mon] : $dt->format('F');
                $year = $dt->format('Y');
                $dayName = mb_convert_case($dayName, MB_CASE_TITLE, 'UTF-8');
                return sprintf('%s %s %s %s', $dayName, $day, $monthName, $year);
            }));

            // French time only: 16h30
            $twig->addFilter(new \Twig\TwigFilter('fr_time', function ($datetime) {
                if (!$datetime) return '';
                try { $dt = new \DateTime($datetime); } catch (\Exception $e) { return $datetime; }
                return $dt->format('H\hi');
            }));
        }

        echo $twig->render($template, View::setDefaultVariables($args));
    }



    /**
     * Ajoute les données à fournir à toutes les pages
     * @param array $args
     * @return array
     */
    public static function setDefaultVariables($args = []){

        $args["user"] = isset($_SESSION['user']) ? $_SESSION['user'] : null;

        // Current path (without query) to help mark active navbar item
        $args["current_path"] = isset($_SERVER['REQUEST_URI'])
            ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
            : '/';

        // flash message support: read from session and clear
        if (isset($_SESSION['flash_message'])) {
            $args['flash_message'] = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
        } else {
            $args['flash_message'] = null;
        }

        return $args;
    }
}
