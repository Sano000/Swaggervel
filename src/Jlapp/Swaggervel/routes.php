<?php
$baseConfig = Config::get('swaggervel::app');
$configs = array($baseConfig);
foreach(Config::get('swaggervel::extra', array()) as $config) {
    $configs[] = array_merge($baseConfig, $config);
}


foreach($configs as $config) {
    Route::any($config['doc-route'].'/{page?}', function($page='api-docs.json') use ($config) {
        $filePath = $config['doc-dir'] . "/{$page}";

        if (!File::Exists($filePath)) {
            App::abort(404, "Cannot find {$filePath}");
        }

        $content = File::get($filePath);
        return Response::make($content, 200, array(
            'Content-Type' => 'application/json'
        ));
    });

    Route::get($config['api-docs-route'], function() use ($config) {
        if ($config['generateAlways']) {
            $appDir = base_path()."/".$config['app-dir'];
            $docDir = $config['doc-dir'];

            if (!File::exists($docDir) || is_writable($docDir)) {
                // delete all existing documentation
                if (File::exists($docDir)) {
                    File::deleteDirectory($docDir);
                }

                File::makeDirectory($docDir);

                $basepath       = "";
                $apiVersion     = "";
                $swaggerVersion = "";
                $excludes       = "";

                $defaultBasePath = $config['default-base-path'];
                if ( ! empty($defaultBasePath)) {
                    $basepath .= " --default-base-path '{$defaultBasePath}'";
                }

                $defaultApiVersion = $config['default-api-version'];
                if ( ! empty($defaultApiVersion)) {
                   $apiVersion = " --default-api-version '{$defaultApiVersion}'";
                }

                $defaultSwaggerVersion = $config['default-swagger-version'];
                if ( ! empty($defaultSwaggerVersion)) {
                   $swaggerVersion = " --default-swagger-version '{$defaultSwaggerVersion}'";
                }

                $exludeDirs = $config['excludes'];
                if (is_array($exludeDirs) && ! empty($exludeDirs)){
                    $excludes = " -e " . implode(":", $exludeDirs);
                }

                $cmd = "php " . base_path() . "/vendor/zircote/swagger-php/swagger.phar $appDir -o {$docDir} {$apiVersion} {$swaggerVersion} {$basepath} {$excludes}";

                $result = shell_exec($cmd);

                //display all swagger-php error messages so that it doesn't fail silently
                if ((strpos($result, "[INFO]") != FALSE) || (strpos($result, "[WARN]") != FALSE) || (strpos($result, "[ERROR]") != FALSE)) {
                    throw new \Exception($result);
                }
            }
        }
        
        if (Config::get('swaggervel::app.behind-reverse-proxy')) {
            $proxy = Request::server('REMOTE_ADDR');
            Request::setTrustedProxies(array($proxy));
        }        

        Blade::setEscapedContentTags('{{{', '}}}');
        Blade::setContentTags('{{', '}}');

        $response = Response::make(
            View::make('swaggervel::index', array(
                'urlToDocs'      => url($config['doc-route']),
                'secure'         => Request::secure(),
                'config'         => $config,
            )),
            200
        );

        if (isset($config['viewHeaders'])) {
            foreach ($config['viewHeaders'] as $key => $value) {
                $response->header($key, $value);
            }
        }

        return $response;
    });
}
