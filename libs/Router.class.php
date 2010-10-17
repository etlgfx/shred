<?php

require_once PATH_LIBS .'Request.class.php';

class Router {
    private $routes;
    private $uri;
    private $method;

    public function __construct() {
        if (file_exists(PATH_APP_TMP .'routes.compiled.php')) {
            $this->routes = require_once PATH_APP_TMP .'routes.compiled.php';
        }
        else {
            $this->routes = $this->compile();
            file_put_contents(PATH_APP_TMP .'routes.conf.compiled.php', '<?php return '. var_export($this->routes, true) .'; ?>');
        }
    }

    /**
     * Get relative path from the base URL. e.g. bla/stuff/1
     *
     * @returns string
     */
    public function getUri() {
        return substr(REQUEST_URI, strlen(SERVER_URL));
    }

    /**
     * @returns Request object
     */
	public function route() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $this->getUri();

		foreach ($this->routes as $route) {
            if (isset($route['method']) && $this->method != $route['method']) {
                continue;
            }

			if (preg_match($route['url'], $this->uri, $matches)) {

				$params = array();
				foreach ($route['actions']['params'] as $m) {
					$params []= $matches[$m];
                }

                return new Request($this->method, $route['actions']['controller'], $route['actions']['action'], $params);
			}
		}

        return $this->defaultRoute();
	}

    /**
     * We couldn't match a custom route, so fall back to the default routing
     * scheme: controller/action/param1/param2/...
     *
     * @returns Request object
     */
    private function defaultRoute() {
        $parts = explode('/', $this->uri);


        $request = new Request($this->method);

        if (isset($parts[0]) && $parts[0]) {
            $request->setController($parts[0]);
        }
        else {
            $request->setController(Config::get('site_config.default_page') ? Config::get('site_config.default_page') : 'default');

            return $request;
        }

        if (isset($parts[1]) && $parts[1]) {
            $request->setAction($parts[1]);
        }
        else {
            return $request;
        }

        for ($i = 2; $i < count($parts); $i++) {
            $request->addParam($parts[$i]);
        }

        return $request;
    }

    /**
     * Compile pretty routing config format to useful fancy arrays
     *
     * @returns array
     */
	public function compile() {
		$routes = Config::get('routes'); //require_once 'routes.conf.php';

		$return = array();

		$i = 0;
		foreach ($routes as $route => $actions) {
			//echo $route . PHP_EOL;

			$return[$i] = array();

			if (strpos($route, ';')) {
				$options = explode(';', $route);

				foreach ($options as $option) {
					if (preg_match('#^[a-z-_]+:.*$#', $option)) {
						list($k, $v) = explode(':', $option, 2);
						$return[$i][$k] = $v;
					}
					else
						throw new Exception('Invalid syntax');
				}

				if (!isset($return[$i]['url'])) {
					throw new Exception('URL portion not set');
				}
			}
			else {
				$return[$i]['url'] = $route;
			}

			$parts = explode('/', $return[$i]['url']);

			$part = 1;
			$return[$i]['matches'] = array();

			foreach ($parts as &$v) {
				/** TODO
				 * Allowing REGEX matches as well opens up a whole nother can of
				 * bitch ass worms, parentheses lists now need to be parsed to
				 * figure out parameter positions
				if (preg_match('#^\[re:(.*)\]$#', $v, $matches)) {
					//echo 'regex token: '. $v . PHP_EOL;

					$v = $matches[1];
				}
				else*/
				if (preg_match('#^\[([a-z_]+:){0,1}(date|slug|id)\]$#', $v, $matches)) {
					/*
					echo 'other token: '. $v;
					var_export($matches);
					echo PHP_EOL;
					*/

					switch ($matches[2]) {
						case 'date':
							//echo
							$v = '(\d{4}/\d{1,2}/\d{1,2})';
							break;
						case 'slug':
							//echo
							$v = '([a-z0-9]+[a-z0-9-]*[a-z0-9]*)';
							break;
						case 'id':
							//echo
							$v = '([a-z0-9]+)';
							break;
					}

					if ($matches[1]) {
						$return[$i]['matches'][$part++] = array(substr($matches[1], 0, -1) => $matches[2]);
					}
					else {
						$return[$i]['matches'][$part++] = array($matches[2]);
					}
					//echo PHP_EOL;
				}
			}

			$return[$i]['url'] = '#^'. implode('/', $parts) .'(/.*)?$#';
			$return[$i]['actions'] = $actions; 

			$params = array();
			
			foreach ($actions['params'] as $action) {

				if (strpos($action, ':')) {
					list($k_action, $v_action) = explode(':', $action);

					$is_mapped = false;

					foreach ($return[$i]['matches'] as $j => $match) {
						if (array($k_action => $v_action)  == $match) {
							$params []= $j;
							$is_mapped = true;
							break;
						}
					}

					if (!$is_mapped) {
						throw new Exception('Unable to map parameter '. $action .' to a regex match');
                    }
				}
				else {
					$is_mapped = false;

					foreach ($return[$i]['matches'] as $j => $match) {
						if ($action == reset($match) || $action === key($match)) {
							$params []= $j;
							$is_mapped = true;
							break;
						}
					}

					if (!$is_mapped) {
						throw new Exception('Unable to map parameter '. $action .' to a regex match');
                    }
				}
			}

			$return[$i]['actions']['params'] = $params; 

			$i++;
		}

		return $return;
	}
}

/*
$router = new Router();
$router->route('blog/2010/10/10/slug-part-thing/page/1');
$router->route('company/2010/user/10');
$router->route('no/matches/here/jerk');
$router->route('gallery/no/match/');
$router->route('blog/gallery');
$router->route('site/1/user/2');
$router->route('blog/2010/10/12/slug-part-thing/page/1');
$router->route('blog/2010/10/13/slug-part-thing/page/1');
*/

?>