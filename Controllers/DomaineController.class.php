<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class DomaineController
{
    private $db;
    private $fileUrl;
    private $logFile;

    public function __construct($dbHost, $dbName, $dbUser, $dbPass, $fileUrl, $logFile)
    {
        $this->db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->fileUrl = $fileUrl;
        $this->logFile = $logFile;
    }


    public function writeLog($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function extractDomain($domain)
    {
        $domain = str_replace('.fr', '', $domain);
        $domainParts = explode('.', $domain);

        if (count($domainParts) >= 2) {
            return $domainParts[count($domainParts) - 2] . '.' . $domainParts[count($domainParts) - 1];
        }

        return $domain;
    }

    public function processFileContent($date = NULL)
    {
        // Get the current date minus one day if no date is provided
        if ($date === null) {
            $fileDate = date('Ymd', strtotime('-1 day'));
        } else {
            $fileDate = $date;
        }

        // Construct the URL with the dynamic date
        $url = 'https://www.afnic.fr/wp-media/ftp/domaineTLD_Afnic/' . $fileDate . '_CREA_fr.txt';

        $client = new Client();
        $response = $client->get($url); // Use the dynamic URL
        $fileContent = $response->getBody();

        $lines = explode("\n", $fileContent);
        foreach ($lines as $line) {
            $domain = $this->extractDomain(trim($line));

             // Skip lines without a ".fr" domain
            if (!empty($line) && $line[0] !== '#') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM domaines WHERE nom = :domain AND date = :date");
                $stmt->bindValue(':domain', $domain);
                $stmt->bindParam(':date', $fileDate);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    $stmt = $this->db->prepare('INSERT INTO domaines (id, nom, date) VALUES (NULL, :domain, :date)');
                    $stmt->bindParam(':domain', $domain);
                    $stmt->bindParam(':date', $fileDate);
                    $stmt->execute();

                    $this->writeLog("Domaine: $domain, Date: $fileDate");
                    echo 'Script executed successfully!';
                } else {
                    $this->writeLog("Skipping duplicate domain: $domain, Date: $fileDate");
                    echo 'Script skipped!';
                }
            }
        }
    }
}

