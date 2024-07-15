<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeService extends Command
{
    protected $signature = 'make:service {name} {--type=blade}';
    protected $description = 'Create a new service with repository, controller, model, migration, and optionally transformer or livewire component';

    public function handle()
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        
        $this->createRepository($name);
        $this->createInterface($name);
        $this->bindInServiceProvider($name);
        $this->createModelAndMigration($name);
        
        if ($type == 'api') {
            $this->createController($name, $type);
            $this->createRequests($name);
            $this->createTransformer($name);
        } else {
            $this->createLivewireComponent($name);
        }

        $this->info("Service {$name} created successfully.");
    }

    protected function getStubContent($stub)
    {
        return File::get(base_path("stubs/{$stub}.stub"));
    }

    protected function createRepository($name)
    {
        $repositoryClass = "Eloquent{$name}Repository";
        $interface = "{$name}Repository";
        $path = app_path("Repositories/{$name}/Eloquent/{$repositoryClass}.php");

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        $stub = $this->getStubContent('eloquent-repository');
        $content = str_replace(['{{class}}', '{{interface}}'], [$name, $interface], $stub);

        File::put($path, $content);
    }

    protected function createInterface($name)
    {
        $interface = "{$name}Repository";
        $path = app_path("Repositories/{$name}/{$interface}.php");

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        $stub = $this->getStubContent('repository-interface');
        $content = str_replace(['{{interface}}', '{{class}}'], [$interface, $name], $stub);

        File::put($path, $content);
    }

    protected function bindInServiceProvider($name)
    {
        $interface = "{$name}Repository";
        $bindingStub = $this->getStubContent('binding');
        $binding = str_replace(['{{class}}', '{{interface}}'], [$name, $interface], $bindingStub);
        $useInBinding = $this->getStubContent('use-in-bind');
        $useInBindingContent = str_replace('{{class}}', $name, $useInBinding);
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (File::exists($providerPath)) {
            $content = File::get($providerPath);

            if (strpos($content, $useInBindingContent) === false) {
                $content = str_replace(
                    "use Illuminate\Support\ServiceProvider;\n",
                    "$useInBindingContent\n".
                    "use Illuminate\Support\ServiceProvider;\n",
                    $content
                );

                File::put($providerPath, $content);
            }
            
            if (strpos($content, $binding) === false) {
                $content = str_replace(
                    "public function register()\n    {",
                    "public function register()\n    {\n        {$binding}\n",
                    $content
                );

                File::put($providerPath, $content);
            }


        }
    }

    protected function createModelAndMigration($name)
    {
        $this->call('make:model', ['name' => $name, '--migration' => true]);
    }

    protected function createController($name, $type)
    {
        $controllerName = "{$name}Controller";
        $path = app_path("Http/Controllers/API/V1/{$controllerName}.php");
        $lowerName = lcfirst($name);
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }
        
        $stub = $this->getStubContent('controller');
        $content = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $stub);

        File::put($path, $content);
    }

    protected function createTransformer($name)
    {
        $transformerName = "Show{$name}Transformer";
        $path = app_path("Transformers/$name/{$transformerName}.php");

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        $stub = $this->getStubContent('transformer');
        $content = str_replace('{{class}}', $name, $stub);

        File::put($path, $content);
    }

    protected function createLivewireComponent($name)
    {

        $lowerName = lcfirst($name);
        $viewFolderName = Str::kebab($name);
        $createComponentName = "Create{$name}Modal";
        $createViewName = Str::kebab($createComponentName);

        $createComponentpath = app_path("Livewire/$name/{$createComponentName}.php");
        if (!File::exists(dirname($createComponentpath))) {
            File::makeDirectory(dirname($createComponentpath), 0755, true);
        }

        $createViewtpath = resource_path("view/livewire/$viewFolderName/{$createViewName}.blade.php");
        if (!File::exists(dirname($createViewtpath))) {
            File::makeDirectory(dirname($createViewtpath), 0755, true);
        }


        $editComponentName = "Edit{$name}Modal";
        $editViewName = Str::kebab($editComponentName);

        $editComponentpath = app_path("Livewire/$name/{$editComponentName}.php");
        if (!File::exists(dirname($editComponentpath))) {
            File::makeDirectory(dirname($editComponentpath), 0755, true);
        }

        $editViewtpath = resource_path("view/livewire/$viewFolderName/{$editViewName}.blade.php");
        if (!File::exists(dirname($editViewtpath))) {
            File::makeDirectory(dirname($editViewtpath), 0755, true);
        }

        $listComponentName = "List{$name}s";
        $listViewName = Str::kebab($listComponentName);

        $listComponentpath = app_path("Livewire/$name/{$listComponentName}.php");
        if (!File::exists(dirname($listComponentpath))) {
            File::makeDirectory(dirname($listComponentpath), 0755, true);
        }

        $listViewtpath = resource_path("view/livewire/$viewFolderName/{$listViewName}.blade.php");
        if (!File::exists(dirname($listViewtpath))) {
            File::makeDirectory(dirname($listViewtpath), 0755, true);
        }

        $createComponentStub = $this->getStubContent('livewire/class/create-modal');
        $createComponentContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $createComponentStub);
        $createViewStub = $this->getStubContent('livewire/view/create-modal');
        $createViewContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $createViewStub);
        File::put(app_path("Livewire/$name/{$createComponentName}.php"), $createComponentContent);
        File::put(resource_path("views/livewire/$viewFolderName/{$createViewName}.blade.php"), $createViewContent);

        $editComponentStub = $this->getStubContent('livewire/class/edit-modal');
        $editComponentContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $editComponentStub);
        $editViewStub = $this->getStubContent('livewire/view/edit-modal');
        $editViewContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $editViewStub);
        File::put(app_path("Livewire/$name/{$editComponentName}.php"), $editComponentContent);
        File::put(resource_path("views/livewire/$viewFolderName/{$editViewName}.blade.php"), $editViewContent);


        $listComponentStub = $this->getStubContent('livewire/class/list');
        $listComponentContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $listComponentStub);
        $listViewStub = $this->getStubContent('livewire/view/list');
        $listViewContent = str_replace(['{{class}}', '{{lowerName}}'], [$name, $lowerName], $listViewStub);
        File::put(app_path("Livewire/$name/{$listComponentName}.php"), $listComponentContent);
        File::put(resource_path("views/livewire/$viewFolderName/{$listViewName}.blade.php"), $listViewContent);
    }

    private function createRequests($name){
        $storeRequestName = "Store{$name}Request";
        $storeRequestpath = app_path("Http/Requests/{$name}/{$storeRequestName}.php");
        if (!File::exists(dirname($storeRequestpath))) {
            File::makeDirectory(dirname($storeRequestpath), 0755, true);
        }
        $storeRequestStub = $this->getStubContent('request/store');
        $storeRequestContent = str_replace('{{class}}', $name, $storeRequestStub);
        File::put(app_path("Http/Requests/{$name}/{$storeRequestName}.php"), $storeRequestContent);
        
        $updateRequestName = "Update{$name}Request";
        $updateRequestpath = app_path("Http/Requests/{$name}/{$updateRequestName}.php");
        if (!File::exists(dirname($updateRequestpath))) {
            File::makeDirectory(dirname($updateRequestpath), 0755, true);
        }
        $updateRequestStub = $this->getStubContent('request/update');
        $updateRequestContent = str_replace('{{class}}', $name, $updateRequestStub);
        File::put(app_path("Http/Requests/{$name}/{$updateRequestName}.php"), $updateRequestContent);

        $listRequest = "List{$name}Request";
        $listPath = app_path("Http/Requests/{$name}/{$listRequest}.php");
        if (!File::exists(dirname($listPath))) {
            File::makeDirectory(dirname($listPath), 0755, true);
        }
        $listRequestStub = $this->getStubContent('request/list');
        $listRequestContent = str_replace('{{class}}', $name, $listRequestStub);
        File::put(app_path("Http/Requests/{$name}/{$listRequest}.php"), $listRequestContent);
    }
}