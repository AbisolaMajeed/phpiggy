<?php 

declare(strict_types=1);

use Framework\TemplateEngine;
use App\Config\Paths;
use App\Services\ValidatorService;

return [
    TemplateEngine::class => fn () => new TemplateEngine(Paths::VIEW), //key act as an id for the dependency
    ValidatorService::class => fn () => new ValidatorService()
];