# Laravel CRUD Generator

A powerful Laravel package that generates complete CRUD structure with repository pattern, service classes, and beautiful views.

## Features

- ✅ Complete CRUD generation (Model, Controller, Views, Routes)
- ✅ Repository Pattern implementation
- ✅ Service Classes
- ✅ Form Request Validation
- ✅ Beautiful Bootstrap views
- ✅ Layouts/app.blade.php will be created
- ✅ Customizable fields and validation rules
- ✅ Sample data generation
- ✅ Follows Laravel best practices

## Installation

You can install the package via Composer:

**composer require aminul/crud-generate**

## Uses

php artisan make:crud Product --fields="name:string,sku:string:unique,description:text,price:decimal,quantity:integer,weight:decimal,barcode:string,is_active:boolean"
