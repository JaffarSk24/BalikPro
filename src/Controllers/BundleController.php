<?php

namespace BalikPro\Controllers;

use BalikPro\Models\Bundle;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

class BundleController
{
    private $bundleModel;
    private $logger;

    public function __construct()
    {
        $this->bundleModel = new Bundle();
        $this->logger = new Logger('api.log');
    }

    public function getActiveBundles(): void
    {
        try {
            $searchQuery = $_GET['q'] ?? '';
            $activeOnly = ($_GET['active'] ?? 1) == 1;

            $bundles = $this->bundleModel->getActiveWithServices();

            // Apply search filter if provided
            if ($searchQuery) {
                $bundles = array_filter($bundles, function($bundle) use ($searchQuery) {
                    return stripos($bundle['name'], $searchQuery) !== false ||
                           stripos($bundle['main_service_title'], $searchQuery) !== false ||
                           stripos($bundle['partner_name'], $searchQuery) !== false;
                });
                $bundles = array_values($bundles); // Reindex array
            }

            // Transform data for API response
            $response = array_map(function($bundle) {
                return [
                    'id' => $bundle['id'],
                    'name' => [
                        'sk' => $bundle['name_sk'] ?? null,
                        'ru' => $bundle['name_ru'] ?? null,
                        'uk' => $bundle['name_uk'] ?? null
                    ],
                    'description' => [
                        'sk' => $bundle['description_sk'] ?? null,
                        'ru' => $bundle['description_ru'] ?? null,
                        'uk' => $bundle['description_uk'] ?? null
                    ],
                    'main_service' => [
                        'id' => $bundle['main_service_id'],
                        'title' => [
                            'sk' => $bundle['main_service_title_sk'] ?? null,
                            'ru' => $bundle['main_service_title_ru'] ?? null,
                            'uk' => $bundle['main_service_title_uk'] ?? null
                        ],
                        'description' => [
                            'sk' => $bundle['main_service_description_sk'] ?? null,
                            'ru' => $bundle['main_service_description_ru'] ?? null,
                            'uk' => $bundle['main_service_description_uk'] ?? null
                        ],
                        'price' => (float)$bundle['main_service_price'],
                        'partner' => [
                            'name' => [
                                'sk' => $bundle['partner_name_sk'] ?? null,
                                'ru' => $bundle['partner_name_ru'] ?? null,
                                'uk' => $bundle['partner_name_uk'] ?? null
                            ],
                            'logo' => $bundle['partner_logo'] ?? null
                        ]
                    ],
                    'bonus_services_count' => count($bundle['bonus_services']),
                    'total_savings' => (float)$bundle['total_savings'],
                    'price' => (float)$bundle['main_service_price']
                ];
            }, $bundles);

            Response::success($response);

        } catch (\Exception $e) {
            $this->logger->error("Error in getActiveBundles: " . $e->getMessage());
            Response::error('Chyba pri načítaní balíkov', 500);
        }
    }

    public function getBundleDetail(int $bundleId): void
    {
        try {
            $bundle = $this->bundleModel->getBundleWithServices($bundleId);

            if (!$bundle) {
                Response::error('Balík nebol nájdený', 404);
                return;
            }

            // Transform data for API response
            $response = [
                'id' => $bundle['id'],
                'name' => [
                    'sk' => $bundle['name_sk'] ?? null,
                    'ru' => $bundle['name_ru'] ?? null,
                    'uk' => $bundle['name_uk'] ?? null
                ],
                'description' => [
                    'sk' => $bundle['description_sk'] ?? null,
                    'ru' => $bundle['description_ru'] ?? null,
                    'uk' => $bundle['description_uk'] ?? null
                ],
                'main_service' => [
                    'id' => $bundle['main_service_id'],
                    'title' => [
                        'sk' => $bundle['main_service_title_sk'] ?? null,
                        'ru' => $bundle['main_service_title_ru'] ?? null,
                        'uk' => $bundle['main_service_title_uk'] ?? null
                    ],
                    'description' => [
                        'sk' => $bundle['main_service_description_sk'] ?? null,
                        'ru' => $bundle['main_service_description_ru'] ?? null,
                        'uk' => $bundle['main_service_description_uk'] ?? null
                    ],
                    'price' => (float)$bundle['main_service_price'],
                    'contact_info' => $bundle['main_contact_info'],
                    'partner' => [
                        'id' => $bundle['partner_id'],
                        'name' => [
                            'sk' => $bundle['partner_name_sk'] ?? null,
                            'ru' => $bundle['partner_name_ru'] ?? null,
                            'uk' => $bundle['partner_name_uk'] ?? null
                        ],
                        'logo' => $bundle['partner_logo']
                    ]
                ],
                'bonus_services' => array_map(function($service) {
                    return [
                        'id' => $service['id'],
                        'title' => [
                            'sk' => $service['title_sk'] ?? null,
                            'ru' => $service['title_ru'] ?? null,
                            'uk' => $service['title_uk'] ?? null
                        ],
                        'description' => [
                            'sk' => $service['description_sk'] ?? null,
                            'ru' => $service['description_ru'] ?? null,
                            'uk' => $service['description_uk'] ?? null
                        ],
                        'price' => (float)$service['price'],
                        'nominal_value' => (float)($service['nominal_value'] ?? 0),
                        'contact_info' => $service['contact_info'],
                        'partner' => [
                            'name' => [
                                'sk' => $service['partner_name_sk'] ?? null,
                                'ru' => $service['partner_name_ru'] ?? null,
                                'uk' => $service['partner_name_uk'] ?? null
                            ],
                            'logo' => $service['partner_logo']
                        ]
                    ];
                }, $bundle['bonus_services']),
                'total_savings' => (float)$bundle['total_savings'],
                'price' => (float)$bundle['main_service_price']
            ];

            Response::success($response);

        } catch (\Exception $e) {
            $this->logger->error("Error in getBundleDetail: " . $e->getMessage());
            Response::error('Chyba pri načítaní detailov balíka', 500);
        }
    }
}
