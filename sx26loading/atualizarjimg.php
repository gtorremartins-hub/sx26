<?php
set_time_limit(900);
ini_set('max_execution_time', 900);
// 	
// http://localhost/top_experimentais/robo%20-%20geral/atualiza_imgcaixa_proxy.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Local - Atualização de imagens e detalhes financiamento para Rio de Janeiro a partir da página detalhe do imóvel da caixa</title>
    <script>
        // Função para atualizar a página a cada 10 minutos (600000 milissegundos)
        function atualizarPagina() {
            setInterval(function() {
                location.reload(); // Recarrega a página
            }, (600000)); // 600000 milissegundos = 10 minutos
        }

        // Chama a função assim que o conteúdo da página for carregado
        window.onload = atualizarPagina;
    </script>
</head>
<body>
    <h1>Top Leilão Fácil - Proxy Local - Proxy Local - Atualização de imagens e detalhes financiamento para Rio de Janeiro a partir da página detalhe do imóvel da caixa</h1>
    <p>Esta página será atualizada a cada 1 minutos.</p>
	<hr><hr>

<?php

function removeSpecialCharacters($text) {
    // Remove caracteres especiais, mantendo apenas letras, números e espaços
    return preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
}


function getEndCaixa($idImovelUp, $endURL, $pdoUp){	
	
	// Desativa imóvel com imagem inválida
	if (!preg_match('/https:\/\//', $endURL)) {
		//echo "A string NÃO contém 'http://'.";		
		$updateSql = "UPDATE tlf_imoveis SET `tlf_ativo` = 0, tlf_robocaixa_flag = 1 WHERE `tlf_imoveis_id` = '$idImovelUp';";
		$updateStmt = $pdoUp->prepare($updateSql);

		// Executa a atualização
		$updateStmt->execute();		

		//die('Erro ao acessar o servidor Node.js');
		return false;
	}

	
	// URL do servidor Node.js
	$nodeServerUrl = 'http://localhost:3000/fetch-images';
	$nodeServerUrlOperacao = 'http://localhost:3000/fetch-operacoes';

	// URL que você deseja passar para o Node.js
	// SELECT * FROM `tlf_imoveis` where LOWER(tlf_link_arrematacao) NOT REGEXP 'https://';
	
	$targetUrl = $endURL;//'https://venda-imoveis.caixa.gov.br/sistema/detalhe-imovel.asp?hdnOrigem=index&hdnimovel=8555535798841';
	//$targetUrl = 'http://localhost/topleilaofacil/testeimgcaixa/testeimgs.php';
	// 1. exemplo com duas imagens fakes: http://localhost:3000/fetch-images?url=http://localhost/topleilaofacil/testeimgcaixa/testeimgs.php
	// 2. Simula imagem da caixa: http://localhost/topleilaofacil/testeimgcaixa/listaimgcaixa3.php
	// 3. Pega imagem da caixa OK: http://localhost:3000/fetch-images?url=https://venda-imoveis.caixa.gov.br/sistema/detalhe-imovel.asp?hdnOrigem=index&hdnimovel=8555535798841
	
	// Testa server nodeJS direto
	// http://localhost:3000/fetch-operacoes?url=https%3A%2F%2Fvenda-imoveis.caixa.gov.br%2Fsistema%2Fdetalhe-imovel.asp%3FhdnOrigem%3Dindex%26hdnimovel%3D8787707542735

	// Codifica a URL para uso em uma query string
	$queryUrl = $nodeServerUrl . '?url=' . urlencode($targetUrl);
	$queryUrlOperacao = $nodeServerUrlOperacao . '?url=' . urlencode($targetUrl);


	
	try {
		// http://localhost:3000/fetch-images?url=https%3A%2F%2Fvenda-imoveis.caixa.gov.br%2Fsistema%2Fdetalhe-imovel.asp%3FhdnOrigem%3Dindex%26hdnimovel%3D1444415502686
		// Faz a requisição para o servidor Node.js
		$response = file_get_contents($queryUrl);	
		
		if ($response === FALSE) {
			throw new Exception('Erro ao acessar o servidor Node.js');
			
		}
		// Processar a resposta aqui
	} catch (Exception $e) {
		echo 'Desculpe, ocorreu um erro ao tentar acessar o servidor. Por favor, tente novamente mais tarde.';
		// Log do erro para depuração
		error_log($e->getMessage());
		
	}


	
	// Verifica se houve erro na requisição
	if ($response === FALSE) {
		die('Erro ao acessar o servidor Node.js');
	} elseif (json_decode($response) == 'imovel_removido') {
		// A resposta é a string 'imovel_removido'
		//echo 'O imóvel foi removido';
		// Desativa Imovel
		$updateSql = "UPDATE tlf_imoveis SET `tlf_ativo` = 0, tlf_robocaixa_flag = 1 WHERE `tlf_imoveis_id` = '$idImovelUp';";
		$updateStmt = $pdoUp->prepare($updateSql);

		// Executa a atualização
		$updateStmt->execute();	
		
		// Inseri na temporária para que imóvel a ser removido por engano não fique sendo requisitado em looping, uma vez que o mesmo é verificado e identificado que foi removido incorretamente da planilha da caixa, já que o mesmo continua aceitando oferta
		$sql = "INSERT IGNORE INTO `tlf_imoveis_temp`(`tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo`) SELECT `tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo` 
				FROM `tlf_imoveis` 
				WHERE `tlf_imoveis_id` = '" . $idImovelUp . "' 
				and not exists(SELECT * FROM `tlf_imoveis_temp` WHERE `tlf_link_arrematacao` = '" . htmlentities($endURL) . "')";
		echo '<p>Sql inserir dado na temporária: ' . $sql . ' <br><hr></p>';		
		$stmt = $pdoUp->prepare($sql);
		$stmt->execute();	
		
		echo '<p>Imóvel Vendido: ';
		echo $updateSql . "<br>";
		echo '</p>';

		
		return true;
		
	} else { // Inicio Imagens encontradas e Imovel não removido
		
		$responseOperacao = file_get_contents($queryUrlOperacao);
		
		// Decodifica a resposta JSON
		$imageSrcs = json_decode($response);
				
		$datanaceita = json_decode($responseOperacao, true);

		// Verifica se a decodificação foi bem-sucedida
		if (json_last_error() !== JSON_ERROR_NONE) {
			die('Erro ao decodificar a resposta JSON, tente novamente mais tarde!');
		}


		// Prepara a consulta de atualização dinamicamente
		$i = 1;
		$updateFields = [];
		$params = [];
		
		// Exibe os srcs das imagens
		//echo "<ul>";
		foreach ($imageSrcs as $src) {
			//echo "<li><img src='https://venda-imoveis.caixa.gov.br/$src' alt='Imagem'></li>";
			echo '<p>';
			echo $src . "<br>";
			echo '<img src="https://venda-imoveis.caixa.gov.br' . $src . '"><br>';
			echo '</p>';		
		
			if($i >= 1 && $i <=5){
				$updateFields[] = "tlf_imagem_$i = :imagem$i";
				$params[":imagem$i"] = 'https://venda-imoveis.caixa.gov.br' . $src;
			}
			$i++;
		}
		
		// Inicio		
		$isearchText = "";
		$icontainsText = "";
		$pCount = 0;

		foreach ($datanaceita as $p) {

			if($pCount == 2){

				$conteudoli = $p;
				
				echo '<p>';
				echo 'Conteúdo operacoes: ' . $conteudoli . '<br>';
				echo '</p>';	
			
				/*
				// FGTS
				$isearchText = 'Para uso do FGTS, consulte condições e enquadramento.';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_FGTS = 1";
				}else{
					//$updateFields[] = "tlf_FGTS = 0";	

					$isearchText = 'Imóvel NÃO aceita utilização de FGTS.';
					$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
					if ($icontainsText) {
						$updateFields[] = "tlf_FGTS = 0";
					}else{
						$updateFields[] = "tlf_FGTS = 1";				
					}	
					
				}	
				
				// FINANCIAMENTO
				$isearchText = 'Permite financiamento na linha de crédito SBPE (Consulte Condições).';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_financiamento = 1";
					$updateFields[] = "tlf_financiamento_sbpe = 1";
				}else{

					
					$isearchText = 'Imóvel NÃO aceita financiamento habitacional.';
					$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
					if ($icontainsText) {
						$updateFields[] = "tlf_financiamento = 0";
						$updateFields[] = "tlf_financiamento_sbpe = 0";
					}else{
						$updateFields[] = "tlf_financiamento = 1";
						$updateFields[] = "tlf_financiamento_sbpe = 1";		
					}
					
				}

				
				$isearchText = 'Imóvel NÃO aceita parcelamento.';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_parcelamento = 0";
				}else{
					$updateFields[] = "tlf_parcelamento = 1";				
				}
				
				$isearchText = 'Imóvel NÃO aceita consórcio.';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_consorcio = 0";
				}else{
					$updateFields[] = "tlf_consorcio = 1";				
				}	
				
				*/
				
				$isearchText = 'Permite utilização de FGTS';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_FGTS = 1";
				}else{
					$updateFields[] = "tlf_FGTS = 0";				
				}	
				
				
				$isearchText = 'Permite financiamento';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_financiamento = 1";
				}else{
					$updateFields[] = "tlf_financiamento = 0";				
				}
				
				$isearchText = 'Permite financiamento - somente SBPE';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					//$updateFields[] = "tlf_financiamento = 1";
					$updateFields[] = "tlf_financiamento_sbpe = 1";
				}else{
					//$updateFields[] = "tlf_financiamento = 0";	
					$updateFields[] = "tlf_financiamento_sbpe = 0";
				}
				
				
				$updateFields[] = "tlf_parcelamento = 0";
				$updateFields[] = "tlf_consorcio = 0";
				

				$isearchText = 'judicial:';
				$icontainsText = strpos(removeSpecialCharacters($conteudoli), removeSpecialCharacters($isearchText)) !== false;
				if ($icontainsText) {
					$updateFields[] = "tlf_processo = 1";
					
					preg_match('/' . $isearchText . ' (\d+)\./', $conteudoli, $matches);
					if (isset($matches[1])) {
						$numero = $matches[1];
						echo "<p>Número da ação judicial: " . $numero . "</p>";
						$updateFields[] = "tlf_numero_processo = '" . $numero . "'";
					} else {
						echo "<p>Número não encontrado.</p>";
					}

				}else{
					$updateFields[] = "tlf_processo = 0";				
				}	
					
				$updateFields[] = "tlf_robocaixa_flag = 1";
				$updateFields[] = "tlf_ativo = 1";
				
				$i++;
			}
			$pCount++;			
			
		}
		// FIM
				
		//echo "</ul>";
		if($i > 1){
			// Cria a string de atualização com base no número de imagens
			$updateSql = "UPDATE tlf_imoveis SET " . implode(", ", $updateFields) . " WHERE `tlf_imoveis_id` = '$idImovelUp';";
			echo "<p>updateSql >>: " . $updateSql . "</p>";
			$updateStmt = $pdoUp->prepare($updateSql);
			// Executa a atualização
			$updateStmt->execute($params);	
			
			// INICIO: Inserir imovel atualizado na tabela temporária para não rodar consulta novamente, necessário, pois existem imóveis removidos da planilha da caixa que estão disponíveis para compra ainda.

			// Verifica se o registro já existe
			$sql = "SELECT * FROM tlf_imoveis_temp WHERE tlf_link_arrematacao = '?'";
			echo '<p>Sql Verifica se existe: ' . $sql . ' - ' . htmlentities($endURL) . ' <br><hr></p>';
			$stmt = $pdoUp->prepare($sql);
			$stmt->execute();
			// Obtém todos os resultados da consulta
			$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// Verifica se há resultados
			if (count($resultados) == 0) {
			
			
				// Inseri na temporária para que imóvel a ser removido por engano não fique sendo requisitado em looping, uma vez que o mesmo é verificado e identificado que foi removido incorretamente da planilha da caixa, já que o mesmo continua aceitando oferta
				$sql = "INSERT IGNORE INTO `tlf_imoveis_temp`(`tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo`) SELECT `tlf_num_imovel`, `tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_preco_desconto`, `tlf_preco_avaliacao`, `tlf_desconto`, `tlf_descricao`, `tlf_modalidade_venda`, `tlf_link_arrematacao`, `tlf_zona`, `tlf_latitude`, `tlf_longitude`, `tlf_numero_processo`, `tlf_valor_nossa_avaliacao`, `tlf_avaliacao_aluguel`, `tlf_valor_condominio`, `tlf_data_1_leilao`, `tlf_data_2_leilao`, `tlf_imagem_1`, `tlf_imagem_2`, `tlf_imagem_3`, `tlf_imagem_4`, `tlf_imagem_5`, `tlf_tipo_acao`, `tlf_nome_autor_acao`, `tlf_nome_leiloeiro`, `tlf_top_recomendacao`, `tlf_top_leilao`, `tlf_ativo` 
						FROM `tlf_imoveis` 
						WHERE `tlf_imoveis_id` = '" . $idImovelUp . "' 
						and not exists(SELECT * FROM `tlf_imoveis_temp` WHERE `tlf_link_arrematacao` = '" . htmlentities($endURL) . "')";
				echo '<p>Sql inserir dado na temporária: ' . $sql . ' <br><hr></p>';		
				$stmt = $pdoUp->prepare($sql);
				$stmt->execute();
				
			}			
			// FIM: Inserir imovel atualizado na tabela temporária para não rodar consulta novamente, necessário, pois existem imóveis removidos da planilha da caixa que estão disponíveis para compra ainda.
		
			
			return true;
		}else{
			return false;
		}	
	// Fim Imagens encontradas	
	}

}


// Conexão com o banco de dados MySQL
include_once('../db_dados.php');

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obter as imagens com idCategoria = 2 (limitado a 5)
    //$sql = "SELECT srcImagem FROM tlf_imoveis WHERE idCategoria = 2 LIMIT 5";
	//$sql = "SELECT `tlf_imoveis_id`, `tlf_link_arrematacao` FROM `tlf_imoveis` WHERE `tlf_ativo` = 1 and (`tlf_modalidade_venda` <> 'Leilao Judicial' and `tlf_modalidade_venda` <> 'Leilao Extrajudicial') and (tlf_imagem_1 NOT LIKE 'https://venda-imoveis.caixa.gov.br%' or tlf_imagem_1 is null) limit 1000;";
	//$sql = "SELECT `tlf_imoveis_id`, `tlf_link_arrematacao` FROM `tlf_imoveis` WHERE `tlf_ativo` = 1 and (`tlf_modalidade_venda` <> 'Leilao Judicial' and `tlf_modalidade_venda` <> 'Leilao Extrajudicial') and tlf_robocaixa_flag = 0 and tlf_imagem_1 is null limit 300;";
	$sql = "SELECT `tlf_imoveis_id`, `tlf_link_arrematacao` FROM `tlf_imoveis` WHERE `tlf_ativo` = 1 and (`tlf_modalidade_venda` <> 'Leilao Judicial' and `tlf_modalidade_venda` <> 'Leilao Extrajudicial') and tlf_robocaixa_flag = 0 and `tlf_imagem_1` is null
		UNION
		SELECT a.`tlf_imoveis_id`, a.`tlf_link_arrematacao` FROM `tlf_imoveis` a left join `tlf_imoveis_temp` b on b.tlf_link_arrematacao = a.tlf_link_arrematacao where a.tlf_ativo = 1 and (a.`tlf_modalidade_venda` <> 'Leilao Judicial' and a.`tlf_modalidade_venda` <> 'Leilao Extrajudicial') and b.tlf_imoveis_id is null limit 500;";	
	
		
	$stmt = $pdo->prepare($sql);
    $stmt->execute();
	
 // Obtém todos os resultados da consulta
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verifica se há resultados
    if (count($resultados) > 0) {
        // Exibe os resultados em uma tabela HTML
        //echo '<table border="1">';
        //echo '<tr><th>ID</th><th>Link</th></tr>';
        foreach ($resultados as $linha) {			
            //echo '<tr>';
            echo '<p><hr>' . htmlspecialchars($linha['tlf_imoveis_id']) . '</p>';
            echo '<p>' . html_entity_decode($linha['tlf_link_arrematacao']) . '</p>';
			$idImovel = $linha['tlf_imoveis_id'];
			//$url_corrigida = html_entity_decode($linha['tlf_link_arrematacao'], ENT_QUOTES, 'UTF-8');
			$url_corrigida = html_entity_decode($linha['tlf_link_arrematacao'], ENT_QUOTES, 'ISO-8859-1');
			if(getEndCaixa($idImovel, $url_corrigida, $pdo) == true){
				echo '<p>Atualização bem-sucedida.</p><hr>';
			}else{
				echo '<p>Imagem não encontrada.</p><hr>';
			}
			
			//echo '<hr><p>' . getEndCaixa($url_corrigida, $pdo) . '</p><hr>';
            //echo '</tr>';
        }
        //echo '</table>';
    } else {
        echo '<p>Nenhum registro encontrado.</p>';
    }

} catch (PDOException $e) {
    echo "Erro na conexão ou na consulta: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}


?>


</body>
</html>

