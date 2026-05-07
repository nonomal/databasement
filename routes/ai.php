<?php

use App\Http\Middleware\SetCurrentOrganization;
use App\Mcp\Servers\DatabasementServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('databasement', DatabasementServer::class);

Mcp::web('/mcp', DatabasementServer::class)
    ->middleware(['auth:sanctum', SetCurrentOrganization::class]);
