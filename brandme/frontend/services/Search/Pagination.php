<?php

namespace Frontend\Services\Search;

/**
 * Class Pagination
 * An unfortunate case mixed view/business logic, but it looks fairly nice "digg" style
 *
 * @package Frontend\Services\Search
 */
class Pagination
{
    /**
     * @see http://www.strangerstudios.com/sandbox/pagination/diggstyle_function.txt
     * @param        $page
     * @param        $totalitems
     * @param int    $limit
     * @param int    $adjacents
     * @return string
     */
    public static function getPaginator($page = 1, $totalitems, $limit = 10, $adjacents = 2)
    {
        $prev = $page - 1;
        $next = $page + 1;
        $lastpage = ceil($totalitems / $limit);
        $lpm1 = $lastpage - 1;
        $pagination = '';
        $pagination .= '<ul class="pagination">';
        //previous button
        if ($page > 1) {
            $pagination .= '<li><button name="p" value="'.$prev.'" type="submit">« Anterior</button></li>';
        } else {
            $pagination .= '<li><button class="disabled" disabled="disabled" name="p" type="submit">« Anterior</button></li>';
        }
        //pages
        if ($lastpage < 7 + ($adjacents * 2)) {
            for ($counter = 1; $counter <= $lastpage; $counter++) {
                if ($counter == $page) {
                    $pagination .= '<li><button type="button" class="active">'.$counter.'</button></li>';
                } else {
                    $pagination .= '<li><button type="submit" name="p" value="'.$counter.'">'.$counter.'</button></li>';
                }
            }
        } elseif ($lastpage >= 7 + ($adjacents * 2)) {
            //close to beginning; only hide later pages
            if ($page < 1 + ($adjacents * 3)) {
                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {

                    if ($counter == $page) {
                        $pagination .= '<li><button type="button" class="active">'.$counter.'</button></li>';
                    } else {
                        $pagination .= '<li><button type="submit" name="p" value="'.$counter.'">'.$counter.'</button></li>';
                    }
                }
                $pagination .= '<li><button class="disabled" type="button">...</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="'.$lpm1.'">'.$lpm1.'</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="'.$lastpage.'">'.$lastpage.'</button></li>';
                //in middle; hide some front and some back
            } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                $pagination .= '<li><button type="submit" name="p" value="1">1</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="2">2</button></li>';
                $pagination .= '<li><button class="disabled" type="button">...</button></li>';
                for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                    if ($counter == $page) {
                        $pagination .= '<li><button type="button" class="active">'.$counter.'</button></li>';
                    } else {
                        $pagination .= '<li><button type="submit" name="p" value="'.$counter.'">'.$counter.'</button></li>';
                    }
                }
                $pagination .= '<li><button class="disabled" type="button">...</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="'.$lpm1.'">'.$lpm1.'</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="'.$lastpage.'">'.$lastpage.'</button></li>';
            } //close to end; only hide early pages
            else {
                $pagination .= '<li><button type="submit" name="p" value="1">1</button></li>';
                $pagination .= '<li><button type="submit" name="p" value="2">2</button></li>';
                $pagination .= '<li><button class="disabled" type="button">...</button></li>';
                for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++) {
                    if ($counter == $page) {
                        $pagination .= '<li><button type="button" class="active">'.$counter.'</button></li>';
                    } else {
                        $pagination .= '<li><button type="submit" name="p" value="'.$counter.'">'.$counter.'</button></li>';
                    }
                }
            }
        }
        //next button
        $pagination .= '<li>';
        if ($page < $counter - 1) {
            $pagination .= '<button name="p" value="'.$next.'" type="submit">Siguiente »</button>';
        } else {
            $pagination .= '<button class="disabled" disabled="disabled" name="p" type="submit">Siguiente »</button>';
        }
        $pagination .= '</li>';
        $pagination .= '</ul>';

        return $pagination;
    }
}