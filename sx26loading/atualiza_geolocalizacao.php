<?php
set_time_limit(1200);
ini_set('max_execution_time', 1200);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização Automática - Geolocalizacao Enderecos imoveis caixa</title>
    <script>
        // Função para atualizar a página a cada 10 minutos (600000 milissegundos)
        function atualizarPagina() {
            setInterval(function() {
                location.reload(); // Recarrega a página
            }, (600000/10)); // 600000 milissegundos = 10 minutos
        }

        // Chama a função assim que o conteúdo da página for carregado
        window.onload = atualizarPagina;
    </script>
</head>
<body>
    <h1>Top Leilão Fácil</h1>
    <p>Esta página será atualizada a cada 1 minutos.</p>
	<hr><hr>
<?php
// Funcao Inicio

// Função para atualizar latitude e longitude
function atualizarCoordenadas($id, $endereco, $connAtualiza, $tentativa) {
    //global $servername, $username, $password, $dbname;

    // Conecta ao banco de dados
    $conn1 = $connAtualiza;//new mysqli($servername, $username, $password, $dbname);

    // Verifica a conexão
    if ($conn1->connect_error) {
        die("Conexão falhou: " . $conn1->connect_error);
    }

    // Normaliza o endereço
	if($tentativa == 1){	
		$endereco = str_replace(' ', '+', trim($endereco));
	}elseif($tentativa == 2){
		$endereco = str_replace(',  N. SN', '', trim($endereco));
		$endereco = str_replace(',  N.', '', trim($endereco));
		$endereco = str_replace(' ', '+', preg_replace('/\s+/', '+', trim($endereco)));
	}else{
		$endereco = str_replace(' ', '+', preg_replace('/\s+/', '+', trim($endereco)));
	}
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($endereco) . "&format=json&limit=1";

    // Inicializa uma nova sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: MyGeocodingApp/1.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    // Verifica se a requisição foi bem-sucedida
    if ($response === FALSE) {
        echo "Erro ao acessar a API para o endereço: $endereco<br>";
        //$conn1->close();
        return false;
    }

    // Decodifica a resposta JSON
    $data = json_decode($response, true);

    if (!empty($data) && array_key_exists(0, $data)) {		
        // Pega a latitude e longitude
        $latitude = $data[0]['lat'];
        $longitude = $data[0]['lon'];

		// Atualiza a tabela tlf_imoveis com a latitude e longitude
		$stmt = $conn1->prepare("UPDATE tlf_imoveis SET tlf_latitude = ?, tlf_longitude = ? WHERE tlf_imoveis_id = ?");
		$stmt->bind_param("sss", $latitude, $longitude, $id);

        if ($stmt->execute()) {
            echo "Dados atualizados com sucesso para o ID: $id<br>";
			return true;
        } else {
            echo "Erro ao atualizar dados para o ID: $id - " . $stmt->error . "<br>";
			return false;
        }

        $stmt->close();
    } else {
		
		if($tentativa == 3){
			// Atualiza a tabela tlf_imoveis com a latitude e longitude
			$stmt = $conn1->prepare("UPDATE `tlf_imoveis` SET `tlf_imoveis_errogeolocalizacao`='1' WHERE `tlf_imoveis_id`=?");
			$stmt->bind_param("s", $id);

			if ($stmt->execute()) {
				echo "Imovel ($endereco) setado para não atualizar geolocalização após terceira tentativa: $id<br>";
				return true;
			} else {
				echo "Erro ao tentar setar imovel ($endereco) para não atualizar geolocalização após terceira tentativa dados para o ID: $id - " . $stmt->error . "<br>";
				return false;
			}
		}
		
        echo "Nenhum dado encontrado para o endereço: $endereco<br>";
		return false;
    }

    // Fecha a conexão
    //$conn1->close();
}

// Funcao Fim

// Conexão com o banco de dados MySQL
include_once('../db_dados.php');



// Conecta ao banco de dados
$conn = new mysqli($host, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Consulta para ler os endereços da tabela tlf_imoveis_enderecos
$sql = "SELECT `tlf_imoveis_id` AS id,`tlf_zona`,`tlf_uf`, `tlf_cidade`, `tlf_bairro`, `tlf_endereco`, `tlf_latitude`, `tlf_longitude`
, CONCAT(tlf_endereco, ' - ', tlf_bairro) AS adress 
, CONCAT(
        SUBSTRING_INDEX(tlf_endereco, ',', 1), ', ', 
        SUBSTRING_INDEX(SUBSTRING_INDEX(tlf_endereco, ',', 2), ',', -1),
		' - ', tlf_bairro,
		', ', tlf_cidade,
		' - ', tlf_uf
    ) AS endereco_formatado1
, UPPER(CONCAT(
        SUBSTRING_INDEX(tlf_endereco, ',', 1), ', ', 
        SUBSTRING_INDEX(SUBSTRING_INDEX(tlf_endereco, ',', 2), ',', -1),
		' - ', tlf_cidade,
		' - ', tlf_uf
    )
    ) AS endereco_formatado2
, UPPER(CONCAT(
		tlf_bairro,
		' - ', tlf_cidade,
		' - ', tlf_uf
    )
    ) AS endereco_formatado3
FROM `tlf_imoveis` WHERE `tlf_ativo` = 1 and `tlf_imoveis_errogeolocalizacao` = '0' and `tlf_latitude` is null limit 350";
//FROM `tlf_imoveis` WHERE `tlf_top_leilao` = 1 and `tlf_ativo` = 1 and tlf_latitude` is null;";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Processa cada linha da tabela
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
		
		if(atualizarCoordenadas($id, $row['endereco_formatado1'], $conn, 1) == false){
			// Tentativa 2			
			if(atualizarCoordenadas($id, $row['endereco_formatado2'], $conn, 2) == false){
				// Tentativa 3			
				if(atualizarCoordenadas($id, $row['endereco_formatado3'], $conn, 3) == false){
					echo "<hr>Erro para o ID $id na terceira tentativa e endereço " . $row['endereco_formatado3'] . "<hr>";
				}else{
					echo "<hr>Sucesso para o ID $id na terceira tentativa.<hr>";
					
				}
			}
		}

    }
} else {
    echo "Tentativa Geral: Nenhum endereço encontrado na tabela tlf_imoveis consulta: " . $sql;
}

// Fecha a conexão
$conn->close();
?>

</body>
</html>
