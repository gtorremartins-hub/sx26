<?php
// Dados de conexão (string que o Replit mostrou)
$host = "helium";
$dbname = "heliumdb";
$user = "postgres";
$password = "password";

try {
    // DSN para PostgreSQL
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    // Configura para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query: pegar os 10 primeiros registros da tabela
    $sql = "SELECT * 
            FROM u227285753_px96b.tlf_imoveis 
            ORDER BY tlf_imoveis_id ASC 
            LIMIT 10";

    $stmt = $pdo->query($sql);

    // Exibir resultados
    echo "<h2>Primeiros 10 registros da tabela tlf_imoveis</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Número Imóvel</th></tr>";

    foreach ($stmt as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['tlf_imoveis_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tlf_num_imovel']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>
