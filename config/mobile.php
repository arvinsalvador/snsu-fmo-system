<?php

return ['minimum_supported_version' => env('MOBILE_MINIMUM_VERSION'), 'features' => ['mobile_work_order_creation' => true, 'mobile_asset_qr_lookup' => true, 'mobile_maintenance_completion' => true, 'mobile_inventory_usage' => true, 'mobile_evidence_upload' => true, 'mobile_kpi_access' => true], 'uploads' => ['max_kb' => 5120, 'image_mimes' => ['image/jpeg', 'image/png', 'image/webp']]];
