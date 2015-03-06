<?php

Route::any(Config::get("swagger.$group.doc-route").'/{page?}', function($page='api-docs.json') use ($self, $group) {
    $filePath = Config::get("swagger.$group.doc-dir") . "/{$page}";

    if (!File::Exists($filePath)) {
        App::abort(404, "Cannot find {$filePath}");
    }

    $content = File::get($filePath);
    return Response::make($content, 200, array(
        'Content-Type' => 'application/json'
    ));
});

Route::get(Config::get("swagger.$group.api-docs-route"), function() use ($self, $group) {
    if (Config::get("swagger.$group.generateAlways")) {
        $appDir = base_path()."/".Config::get("swagger.$group.app-dir");
        $docDir = Config::get("swagger.$group.doc-dir");

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

            $defaultBasePath = Config::get("swagger.$group.default-base-path");
            if ( ! empty($defaultBasePath)) {
                $basepath .= " --default-base-path '{$defaultBasePath}'";
            }

            $defaultApiVersion = Config::get("swagger.$group.default-api-version");
            if ( ! empty($defaultApiVersion)) {
               $apiVersion = " --default-api-version '{$defaultApiVersion}'";
            }

            $defaultSwaggerVersion = Config::get("swagger.$group.default-swagger-version");
            if ( ! empty($defaultSwaggerVersion)) {
               $swaggerVersion = " --default-swagger-version '{$defaultSwaggerVersion}'";
            }

            $exludeDirs = Config::get("swagger.$group.excludes");
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

    if (Config::get("swagger.$group.behind-reverse-proxy")) {
        $proxy = Request::server('REMOTE_ADDR');
        Request::setTrustedProxies(array($proxy));
    }

    Blade::setEscapedContentTags('{{{', '}}}');
    Blade::setContentTags('{{', '}}');

    $response = Response::make(
        View::make('swaggervel::index', array(
            'secure'         => Request::secure(),
            'urlToDocs'      => url(Config::get("swagger.$group.doc-route")),
            'requestHeaders' => Config::get("swagger.$group.requestHeaders"),
            'apiKey'         => Config::get("swagger.$group.api-key"),
            )
        ),
        200
    );

    if (Config::get("swagger.$group.viewHeaders")) {
        foreach (Config::get("swagger.$group.viewHeaders") as $key => $value) {
            $response->header($key, $value);
        }
    }

    return $response;
});
