<?php

/**
 * 1. Faz o upload do arquivo XLSX para a API do Gemini via Files API
 */
function uploadParaGemini($filePath, $mimeType, $apiKey) {
    $fileData = file_get_contents($filePath);
    $fileSize = filesize($filePath);
    $fileName = basename($filePath);

    // Passo 1: Iniciar o upload resumido
    $urlStart = "https://generativelanguage.googleapis.com/upload/v1beta/files?key=$apiKey";
    
    $headers = [
        "X-Goog-Upload-Protocol: resumable",
        "X-Goog-Upload-Command: start",
        "X-Goog-Upload-Header-Content-Length: $fileSize",
        "X-Goog-Upload-Header-Content-Type: $mimeType",
        "Content-Type: application/json"
    ];

    $metaData = json_encode(["file" => ["display_name" => $fileName]]);

    $ch = curl_init($urlStart);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $metaData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headerText = substr($response, 0, $headerSize);
    curl_close($ch);

    // Extrair a URL de upload dos headers de resposta
    preg_match('/X-Goog-Upload-URL: (.*)/i', $headerText, $matches);
    if (!isset($matches[1])) {
        throw new Exception("Erro ao iniciar o upload para o Gemini.");
    }
    $uploadUrl = trim($matches[1]);

    // Passo 2: Enviar os bytes reais do arquivo
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Length: $fileSize",
        "X-Goog-Upload-Command: upload, finalize"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $result['file'] ?? null; // Retorna informações contendo o 'uri' e 'name'
}

/**
 * 2. Consulta o Gemini enviando a URI do arquivo XLSX e as instruções do Markdown
 */
function sanitizarBaseExcel($fileUri, $instrucoesMarkdown, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => "Aqui estão as instruções de sanitização:\n\n" . $instrucoesMarkdown
                    ],
                    [
                        'file_data' => [
                            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'file_uri'  => $fileUri
                        ]
                    ],
                    [
                        'text' => "Por favor, aplique as regras acima na base de dados enviada e retorne o resultado limpo estritamente no formato CSV (separado por vírgulas ou ponto e vírgula), sem comentários adicionais, para que eu possa salvá-lo."
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'text/plain'
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    return $result["candidates"][0]["content"]["parts"][0]["text"] ?? '';
}

// ==========================================
// EXEMPLO DE USO
// ==========================================

$apiKey = 'SUA_API_KEY_AQUI';
$caminhoXlsx = __DIR__ . '/base_dados.xlsx';
$caminhoMarkdown = __DIR__ . '/regras_sanitizacao.md';
$caminhoSaidaCsv = __DIR__ . '/base_limpa.csv'; // Ou .xlsx se preferir manipular

try {
    // 1. Ler as instruções do arquivo Markdown
    if (!file_exists($caminhoMarkdown)) {
        throw new Exception("Arquivo de instruções Markdown não encontrado.");
    }
    $instrucoesMarkdown = file_get_contents($caminhoMarkdown);

    // 2. Fazer o Upload do XLSX para o Gemini
    echo "Enviando arquivo XLSX para o Gemini...\n";
    $fileInfo = uploadParaGemini($caminhoXlsx, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $apiKey);
    
    if (!isset($fileInfo['uri'])) {
        throw new Exception("Falha ao obter a URI do arquivo enviado.");
    }

    // 3. Processar a Inteligência Artificial
    echo "Processando sanitização com base nas regras do Markdown...\n";
    $respostaCsv = sanitizarBaseExcel($fileInfo['uri'], $instrucoesMarkdown, $apiKey);

    // 4. Salvar o resultado retornado em um arquivo legível por planilha
    file_put_contents($caminhoSaidaCsv, $respostaCsv);
    echo "Processo concluído com sucesso! Arquivo salvo em: " . $caminhoSaidaCsv;

} catch (Exception $e) {
    echo "Erro: " . $e.getMessage();
}