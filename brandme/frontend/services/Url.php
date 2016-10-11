<?php
namespace Frontend\Services;

use Common\Services\Sql;


/**
 * Class Url
 * @package Frontend\Services
 */
class Url
{

    /**
     * Parses the current route and searches for suitable breadcrumb URL's
     *
     * @param $route
     * @param $url
     * @return array
     */
    public function prepareBreadcrumbs($route, $url)
    {
        $sql = 'SELECT url, label, parameters FROM url WHERE FIND_IN_SET(id, (SELECT breadcrumbs FROM url where url = "' . $route->getPattern() . '"))';
        $breadcrumbs = Sql::find($sql);
        if (!$breadcrumbs) {
            //route doesn't have any registered breadcrumbs
            return [];
        }
        //add current router onto end of breadcrumb array
        $breadcrumbs[] = Sql::find('SELECT label FROM url where url = "' . $route->getPattern() . '"')[0];
        $currentRouteParameters = [];
        //extract GET variables from current router and insert them into breadcrumbs where pragmatically possible and only if necessary
        foreach ($breadcrumbs as $i => $breadcrumb) {
            //breadcrumb needs GET parameters, we will try harvest them
            if (isset($breadcrumb['url']) && !is_null($breadcrumb['parameters'])) {
                if (empty($currentRouteParameters)) {
                    foreach ($route->getPaths() as $path => $currentRouteParametersParamPos) {
                        if (!in_array($path, ['module', 'namespace', 'controller', 'action'])) {
                            $currentRouteParameters[$path] = $currentRouteParametersParamPos;
                        }
                    }
                }
                $breadcrumb['parameters'] = explode(',', $breadcrumb['parameters']);
                //iterate through the current matched route and try match GET params into breadcrumb URL
                foreach ($breadcrumb['parameters'] as $breadcrumbParamPos => $breadcrumbParam) {
                    if (!isset($currentRouteParameters[$breadcrumbParam])) {
                        //no point in continuing since there is a get param we cannot find for the breadcrumb URL
                        break;
                    }
                    //We have a match, now we need to replace currentRouterParameters param into the new param
                    $placeholderValues = [];
                    preg_match_all($route->getCompiledPattern(), $url, $placeholderValues);
                    //first extract the param from current route according to position
                    $param = $placeholderValues[$currentRouteParameters[$breadcrumbParam]][0];
                    //replace param into breadcrumb url - non greedy
                    $breadcrumbs[$i]['url'] = preg_replace('/\(.+?\)/', $param, $breadcrumbs[$i]['url'], 1);
                }
                //If we were unsuccessful in replacing the breadcrumb URL's param place holders remove the URL completely
                if (!$breadcrumbs[$i]['url'] || preg_match('/\(/', $breadcrumbs[$i]['url'])) {
                    unset($breadcrumbs[$i]['url']);
                }
            }
        }
        return $breadcrumbs;
    }

}