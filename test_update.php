<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/projects/3', 'POST', [
    '_method' => 'PUT',
    'name' => 'Test Project',
    'description' => 'Test Description',
    'coordinator_ids' => [1, 2]
]);
$request->setUserResolver(function() { return \App\Models\User::find(1); });

$controller = app()->make(\App\Http\Controllers\Api\ProjectController::class);
try {
    $response = $controller->update($request, \App\Models\Project::find(3));
    echo "SUCCESS: " . $response->status() . "\n";
    echo $response->getContent();
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
