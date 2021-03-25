<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'LogManager.php';
const GITHUB_TOKEN = "";
const PAGE_SIZE = 100;
const SEARCH_LIMIT = 1000;
const CSV_DELIMITER = ';';

$GitHubData = new GitHubData();
$GitHubData->start();


class GitHubData
{
    private $fp;
    private $lastPageSearchedCount;
    private $currentPageSearchedCount;
    private $searchedAmount;


    public function start()
    {
        $this->fp = fopen('results.csv', 'w');

        $csvHeader = array('createdAt', 'nameWithOwner', 'stargazerCount', 'releases');
        fputcsv($this->fp, $csvHeader, CSV_DELIMITER);

        $lastCursor = 'null';
        $this->searchedAmount = 0;
        $this->lastPageSearchedCount = 0;
        $this->currentPageSearchedCount = PAGE_SIZE;

        while ($this->searchedAmount <= SEARCH_LIMIT) {
            $lastCursor = $this->runQuery($lastCursor, $this->currentPageSearchedCount);

            if ($lastCursor === 'null' || empty($lastCursor)) {
                break;
            }
        }

        fclose($this->fp);
    }

    private function runQuery($lastCursor, $pageSize)
    {
        if($this->searchedAmount + $pageSize > SEARCH_LIMIT){
            $pageSize = SEARCH_LIMIT - $this->searchedAmount;
        }

        if($pageSize <= 0){
            return 'null';
        }

        $query = $this->getSearch($lastCursor, $pageSize);
        LogManager::debug("Iniciando busca com cursor $lastCursor. $pageSize por pagina");

        $response = $this->makeRequest($query);
        $responseList = json_decode($response, true);
        $nextCursor = '"' . $responseList["data"]["search"]["pageInfo"]["endCursor"] . '"' ?? 'null';

        try {
            $this->processPage($responseList);
        } catch (Exception $e) {
            LogManager::debug($e->getMessage());
            if ($pageSize == 1) {
                LogManager::debug('Numero de tentativas maximas esgotadas. ');
                return 'null';
            } else {
                sleep(5);
                return $this->runQuery($lastCursor, $pageSize - 1);
            }
        }

        $this->currentPageSearchedCount = $pageSize;

        if($pageSize == $this->lastPageSearchedCount){
            $this->currentPageSearchedCount++;
        }

        $this->lastPageSearchedCount = $pageSize;
        $this->searchedAmount += $pageSize;
        LogManager::debug("Processados $this->searchedAmount/".SEARCH_LIMIT);

        return $nextCursor;
    }


    private function processPage($responseList)
    {
        if (!isset($responseList["data"]["search"]["nodes"])) {
            throw new Exception($responseList["errors"][0]["message"] ?? 'Erro desconhecido ao buscar dados do GitHub');
        }

        LogManager::debug(count($responseList["data"]["search"]["nodes"]) . ' registros buscados');

        foreach ($responseList["data"]["search"]["nodes"] as $repositoryItem) {
            $csvLine = array();
            $csvLine[] = $repositoryItem['createdAt'] ?? '';
            $csvLine[] = $repositoryItem["nameWithOwner"] ?? '' . ';';
            $csvLine[] = $repositoryItem["stargazerCount"] ?? '';
            $csvLine[] = $repositoryItem["releases"]['totalCount'] ?? '';

            fputcsv($this->fp, $csvLine, CSV_DELIMITER);
        }

        LogManager::debug(count($responseList["data"]["search"]["nodes"]) . ' registros salvos');
    }

    private function makeRequest($query)
    {
        $json = json_encode(['query' => $query, 'variables' => '']);
        $chObj = curl_init();
        curl_setopt($chObj, CURLOPT_URL, 'https://api.github.com/graphql');
        curl_setopt($chObj, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($chObj, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chObj, CURLOPT_POSTFIELDS, $json);
        curl_setopt($chObj, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chObj, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt(
            $chObj,
            CURLOPT_HTTPHEADER,
            array(
                'User-Agent: PHP Script',
                'Content-Type: application/json;charset=utf-8',
                'Authorization: bearer ' . GITHUB_TOKEN
            )
        );

        $response = curl_exec($chObj);

        return $response;
    }

    private function getSearch($cursor, $searchSize)
    {
        $query = <<<EOD
        {
            search(query: "stars:>100 language:Java", type: REPOSITORY, first: $searchSize, after: $cursor) {
              pageInfo {
                startCursor
                hasNextPage
                endCursor
              }
              nodes {
                ... on Repository {
                  nameWithOwner
                  createdAt
                  stargazerCount
                  releases {
                    totalCount
                  }
                  primaryLanguage {
                    name
                  }
                }
              }
            }
          }
          
      
EOD;

        return $query;
    }
}
