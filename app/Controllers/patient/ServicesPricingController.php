<?php

namespace App\Controllers\patient;

class ServicesPricingController
{
    public function handle()
    {
        global $pdo;

        require_once \basePath('app/Models/ServiceModel.php');

        $serviceModel = new \ServiceModel($pdo);
        $activeServices = $serviceModel->getActiveServices();

        // Group active services by category
        $groupedServices = [];
        foreach ($activeServices as $service) {
            $groupedServices[$service['category']][] = $service;
        }

        return [
            'activeServices' => $activeServices,
            'groupedServices' => $groupedServices,
            'categories' => array_keys($groupedServices)
        ];
    }
}
