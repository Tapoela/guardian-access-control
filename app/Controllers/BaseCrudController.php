<?php

namespace App\Controllers;

abstract class BaseCrudController extends BaseController
{
    protected string $viewPath = '';
    protected string $route = '';
}