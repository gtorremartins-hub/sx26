<?php
// Conexão com o banco de dados MySQL
include_once('../db_dados.php');

/*

SELECT t1.*
FROM tlf_imoveis t1
JOIN (
    SELECT tlf_num_imovel, MIN(tlf_imoveis_id) AS id_remover
    FROM tlf_imoveis
    GROUP BY tlf_num_imovel
    HAVING COUNT(*) > 1
) t2 ON t1.tlf_num_imovel = t2.tlf_num_imovel
     AND t1.tlf_imoveis_id = t2.id_remover;

Remove Duplicados

DELETE t1
FROM tlf_imoveis t1
JOIN (
    SELECT tlf_num_imovel, MIN(tlf_imoveis_id) AS id_remover
    FROM tlf_imoveis
    GROUP BY tlf_num_imovel
    HAVING COUNT(*) > 1
) t2 ON t1.tlf_num_imovel = t2.tlf_num_imovel
     AND t1.tlf_imoveis_id = t2.id_remover;




function ConverteValorNumber($valor){
	$valor = str_replace('.', '', $valor);
	$valor = str_replace(',', '.', $valor);

	// Converte a string para float
	$valor = (float)$valor;

	return number_format($valor, 2, '.', '');
}

//v2
function ConverteValorNumber($valor){
    $valor = trim($valor);

    // Se houver vírgula, assumimos que é separador decimal (padrão BR)
    if (strpos($valor, ',') !== false) {
        // Remove pontos de milhar
        $valor = str_replace('.', '', $valor);
        // Troca vírgula decimal por ponto
        $valor = str_replace(',', '.', $valor);
    }

    // Converte para float
    $valor = (float)$valor;

    // Retorna com 2 casas decimais
    return number_format($valor, 2, '.', '');
}

v3 sem decimal
*/
function ConverteValorNumber($valor){
    $valor = trim($valor);

    // Remove pontos de milhar
    $valor = str_replace('.', '', $valor);

    // Se houver vírgula, pega só a parte antes dela
    if (strpos($valor, ',') !== false) {
        $valor = explode(',', $valor)[0];
    }

    // Se houver ponto decimal (caso venha nesse formato), pega só a parte antes dele
    if (strpos($valor, '.') !== false) {
        $valor = explode('.', $valor)[0];
    }

    // Retorna como número inteiro
    return (int)$valor;
}



function salvaCSV($new_file_path){

	// Configurações iniciais
	$url = 'https://venda-imoveis.caixa.gov.br/listaweb/Lista_imoveis_RJ.csv?1058727718';	

	// Define o User-Agent para simular um navegador real
	$headers = [
		"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3",
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
		"Accept-Language: en-US,en;q=0.9"
	];

	// Inicializa o cURL
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Se o site usar SSL, você pode precisar desabilitar a verificação do certificado


	// Executa a requisição
	$response = curl_exec($ch);
	curl_close($ch);
	
    // Converte a codificação para UTF-8
    //$response_utf8 = mb_convert_encoding($response, 'UTF-8', 'auto');
	//$response_utf8 = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');

    // Salva o conteúdo convertido no arquivo
    file_put_contents($new_file_path, $response);

	// Lê o arquivo e remove as linhas 1, 2 e 4
	$lines = file($new_file_path);
	unset($lines[0], $lines[1], $lines[3]);

    // Salva o arquivo novamente com as linhas removidas
    file_put_contents($new_file_path, implode('', $lines));

	echo "<p>Arquivo salvo como: " . $new_file_path . "</p>";

}

function BuscaZonaporCidadeBairro($conn,$cidadebairro){


	// Definir a consulta SQL
	$sql = "SELECT `tlf_imoveis_zona_id`, `tlf_imoveis_zona_cidade_dexpara`, `tlf_imoveis_zona_bairro`, `tlf_imoveis_zona_nome`, `tlf_imoveis_zona_descricao`, `tlf_imoveis_zona_data_criacao` FROM `tlf_imoveis_zona` WHERE `tlf_imoveis_zona_cidade_dexpara` = '$cidadebairro' limit 1";

	// Executar a consulta e verificar se há resultados
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		// Exibir os resultados em uma tabela HTML
		
		while($row = $result->fetch_assoc()) {
			return $row["tlf_imoveis_zona_nome"];
		}
	} else {
		return "Outros";
	}

	
}

function leCSVeGravaBD($conn,$new_file_path){

	// Nome do arquivo CSV
	$filename = $new_file_path;
	$i = 0;
	$inovos = 0;
	//$filename = 'imoveis_2024-09-16.csv';
	
	// apaga tabela antes de carregar com todos os imoveis disponiveis RJ
	$updateSql = "DELETE FROM `tlf_imoveis_temp` WHERE 1;";		
	$stmt = $conn->prepare($updateSql);
	$stmt->execute();

	// Abre o arquivo CSV
	if (($handle = fopen($filename, "r")) !== FALSE) {
		// Pula a primeira linha (cabeçalho)
		fgetcsv($handle, 1000, ";");

		// Laço para ler cada linha do CSV
		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			// Remove espaços antes e depois de cada valor
			$data = array_map('trim', $data);	

			// Converte a codificação de caracteres para UTF-8
			$data = array_map(function($value) {
				return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
			}, $data);

			if (isset($data[10])) {

				$link_de_acesso = $data[10];
				
				$tlf_preco_desconto = "";
				$tlf_preco_avaliacao = "";
				$tlf_preco_desconto = ConverteValorNumber($data[5]);
				$tlf_preco_avaliacao = ConverteValorNumber($data[6]);
				
				$cidade = strtoupper(trim($data[2]));
				$bairro = strtoupper(trim($data[3]));
				if($cidade == 'RIO DE JANEIRO'){
					$cidadebairro = $cidade . $bairro;
				}else{
					$cidadebairro = $cidade;
				}
				$Zona = BuscaZonaporCidadeBairro($conn,$cidadebairro);
				
				// Prepara a inserção no banco de dados
				//$sql = "INSERT INTO tlf_imoveis (`N° do imóvel`, `UF`, `Cidade`, `Bairro`, `Endereço`, `Preço`, `Valor de avaliação`, `Desconto`, `Descrição`, `Modalidade de venda`, `Link de acesso`) //VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$sql = "INSERT INTO `tlf_imoveis_temp`(`tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo`) VALUES (LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),(LTRIM(RTRIM(?))/100),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM('$Zona')),null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,0,1);";
				//echo '<p>SQL: ' . $sql . '<br> Cidadebairro: ' . $cidadebairro . ' <br> Endereco: ' . $data[4] . '<hr></p>';
				//echo '<p>Cidadebairro: ' . $cidadebairro . ' <br> Endereco: ' . $data[4] . '<br>Link Caixa: <a href="' . $link_de_acesso . '" target="_blank">' . $link_de_acesso . '</a><hr></p>';
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sssssddssss", $data[0], $data[1], $data[2], $data[3], $data[4], $tlf_preco_desconto, $tlf_preco_avaliacao, $data[7], $data[8], $data[9], $data[10]);
				$stmt->execute();
				
				
				// INICIO: Inserir imovel novo na tabela final

				// Verifica se o registro já existe
				//$sql = "SELECT * FROM tlf_imoveis WHERE tlf_link_arrematacao = ?";
				$sql = "SELECT * FROM tlf_imoveis WHERE REPLACE(tlf_link_arrematacao, 'hdnOrigem=index&', '') = ?;";

				
				
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("s", $link_de_acesso);
				$stmt->execute();
				$result = $stmt->get_result();

				if ($result->num_rows == 0) {
				
				
					$sql = "INSERT INTO `tlf_imoveis`(`tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo`) VALUES (LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),(LTRIM(RTRIM(?))/100),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM(?)),LTRIM(RTRIM('$Zona')),null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,0,1);";
					//echo '<p>SQL: ' . $sql . '<br> Cidadebairro: ' . $cidadebairro . ' <br> Endereco: ' . $data[4] . '<hr></p>';
					echo '<p>Inserido: Cidadebairro: ' . $cidadebairro . ' <br> Endereco: ' . $data[4] . '<br>Link Caixa: <a href="' . $link_de_acesso . '" target="_blank">' . $link_de_acesso . '</a><hr></p>';
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("sssssddssss", $data[0], $data[1], $data[2], $data[3], $data[4], $tlf_preco_desconto, $tlf_preco_avaliacao, $data[7], $data[8], $data[9], $data[10]);
					$stmt->execute();
					
					$inovos++;
				}else{
					$updateSql = "update `tlf_imoveis` set `tlf_preco_desconto` = LTRIM(RTRIM('" . $tlf_preco_desconto . "')) , `tlf_desconto` = LTRIM(RTRIM('" . $data[7] . "')) ,`tlf_modalidade_venda` = LTRIM(RTRIM('" . $data[9] . "')) WHERE REPLACE(tlf_link_arrematacao, 'hdnOrigem=index&', '') = '" . $link_de_acesso . "';";					
					echo '<p>Atualizado: Cidadebairro: ' . $cidadebairro . ' <br> Endereco: ' . $data[4] . '<br>Link Caixa: <a href="' . $link_de_acesso . '" target="_blank">' . $link_de_acesso . '</a><hr></p>';
					$stmt = $conn->prepare($updateSql);
					$stmt->execute();					
					
				}
				// FIM: Inserir imovel novo na tabela final
				
				
				$i++;
				
			}else{
				echo "Erro: A chave 10 não existe no array. provavelmente o acesso foi negado.";
				return false;
			}
		}
		fclose($handle);
	}

	
	echo "<p>Total de : " . $i . " imóveis inseridos.</p>";
	echo "<p>Total de : " . $inovos . " imóveis novos inseridos.</p>";

}

// Salva o arquivo baixado
$date = date('Y-m-d');
$new_file_path = 'caixacsv\imoveis_rj_' . $date . 'temp.csv';
salvaCSV($new_file_path);

// Conexão com o banco de dados
$conn = new mysqli($host, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
	die("Conexão falhou: " . $conn->connect_error);
}

// executa escrita no banco de dados
leCSVeGravaBD($conn,$new_file_path);

// Fecha a conexão com o banco de dados
$conn->close();



?>
