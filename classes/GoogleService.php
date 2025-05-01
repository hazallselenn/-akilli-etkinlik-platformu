<?php
class GoogleService {
    private $apiKey;
    private $projectId;

    public function __construct($projectId, $apiKey) {
        $this->projectId = $projectId;
        $this->apiKey = $apiKey;
    }

    public function analyzeText($text) {
        $url = 'https://language.googleapis.com/v1/documents:analyzeEntities?key=' . $this->apiKey;

        $postData = [
            'document' => [
                'type' => 'PLAIN_TEXT',
                'content' => $text
            ],
            'encodingType' => 'UTF8'
        ];

        $response = $this->makePostRequest($url, json_encode($postData));
        $data = json_decode($response, true);

        $labels = [];
        if (!empty($data['entities'])) {
            foreach ($data['entities'] as $entity) {
                $labels[] = $entity['name'];
            }
        }
        return $labels;
    }

    public function analyzeImage($imagePath) {
        $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . $this->apiKey;

        $imageData = base64_encode(file_get_contents($imagePath));

        $postData = [
            'requests' => [
                [
                    'image' => ['content' => $imageData],
                    'features' => [
                        ['type' => 'LABEL_DETECTION', 'maxResults' => 10]
                    ]
                ]
            ]
        ];

        $response = $this->makePostRequest($url, json_encode($postData));
        $data = json_decode($response, true);

        $labels = [];
        if (!empty($data['responses'][0]['labelAnnotations'])) {
            foreach ($data['responses'][0]['labelAnnotations'] as $label) {
                $labels[] = $label['description'];
            }
        }
        return $labels;
    }

    private function makePostRequest($url, $jsonData) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
