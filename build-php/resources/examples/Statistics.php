<?php

// Install Criteo Marketing sdk, via composer for example: `composer require criteo/criteo-php-marketing-sdk`
// Then import it as follow:
// require_once(__DIR__ . '/vendor/autoload.php');

use Criteo\Marketing\Api\StatisticsApi;
use Criteo\Marketing\Model\StatsQueryMessageEx;
use Criteo\Marketing\TokenAutoRefreshClient;

/*
 * Although the OpenAPI specification, then this generated client, you can't simply use the API key feature.
 * i.e, the next two lines are useless:
 * $config = Criteo\Marketing\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'YOUR_API_KEY');
 * $config = Criteo\Marketing\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');
 *
 * To benefit from automatic token refresh and to avoid setting the correct value for Authorization header for each call,
 * we introduced a wrapper around Guzzle Http client: TokenAutoRefreshClient.
 * If you don't want this feature, use: GuzzleHttp\Client (or simply don't specify any client parameter).
*/

$clientId = 'YOUR_CLIENT_ID';
$clientCredentials = 'YOUR_PASSWORD';

$apiInstance = new StatisticsApi(new TokenAutoRefreshClient($clientId, $clientCredentials));

$authorization = 'Bearer whatever-value-because-it-is-ignored-when-using-TokenAutoRefreshClient';
$stats_query = new StatsQueryMessageEx(array(
    'report_type'=>"CampaignPerformance",
    'dimensions'=>["CampaignId"],
    'metrics'=>["Clicks"],
    'start_date'=>"2019-01-01",
    'end_date'=>"2019-01-31",
    'format'=>"Csv"
));

try {
    $result = $apiInstance->getStats($authorization, $stats_query);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling StatisticsApi->getStats: ', $e->getMessage(), PHP_EOL;
}
