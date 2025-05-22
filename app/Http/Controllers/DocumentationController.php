<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class DocumentationController extends Controller
{
    /**
     * Show the API documentation UI
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filePath = base_path('docs/swagger-ui.html');
        
        if (!File::exists($filePath)) {
            abort(404, 'API documentation not found');
        }
        
        $content = File::get($filePath);
        return Response::make($content, 200, [
            'Content-Type' => 'text/html',
        ]);
    }
    
    /**
     * Get the OpenAPI specification file
     *
     * @return \Illuminate\Http\Response
     */
    public function openapi()
    {
        $filePath = base_path('docs/openapi.yaml');
        
        if (!File::exists($filePath)) {
            abort(404, 'OpenAPI specification not found');
        }
        
        $content = File::get($filePath);
        return Response::make($content, 200, [
            'Content-Type' => 'application/yaml',
        ]);
    }
}
