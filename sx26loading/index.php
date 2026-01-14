<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Responsivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .menu {
            display: flex;
            justify-content: space-between;
            align-items: ;
            background-color: #7e263d;
            padding: 10px;
        }
        .menu a {
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            display: block;
            width: 100%;
        }
        .menu a:hover {
            background-color: #575757;
        }
        .menu .icon {
            display: none;
            color: white;
            text-align: left;
            padding: 14px 20px;
            cursor: pointer;
        }
        .content {
            margin: 20px;
            text-align: center;
        }
		
        .loader {
            display: none;
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
		
        @media screen and (max-width: 600px) {
            .menu a {
                display: none;
            }
            .menu a.icon {
                display: block;
                color: white;
                text-align: center;
                padding: 14px 20px;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>

<div class="menu">
    <a href="javascript:void(0);" class="icon" onclick="toggleMenu()">☰</a>
	<a href="#" onclick="loadContent('atualizarj.php')">1. Atualização Temp e Novos Imoveis RJ (*)</a>
	<a href="#" onclick="loadContent('atualizarjimg.php')">2 Atualizar Imagem e financiamento RJ - por Proxy (*)</a>	
	<a href="#" onclick="loadContent('atualiza_geolocalizacao.php')">3. Atualizar Geolocalização RJ (nominatim.openstreetmap)</a>
	<a href="#" onclick="loadContent('http://localhost:3000/fetch-images?url=https%3A%2F%2Fvenda-imoveis.caixa.gov.br%2Fsistema%2Fdetalhe-imovel.asp%3FhdnOrigem%3Dindex%26hdnimovel%3D10176203')">4. Testa Server Load</a>
	<a href="http://localhost/topleilaofacil/mapa_rj/?latitude=-22.6210466&longitude=-42.0035516" target="_blank">5. Mapa RJ</a>
	
</div>

<div class="loader" id="loader"></div>

<div class="content" id="content">
    <h2>Bem-vindo!</h2>
    <p>Clique em um item do menu para rodar a respectiva rotina.</p>
	<p><img src="robo.jpg"></p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleMenu() {
        var x = document.getElementsByClassName("menu")[0];
        var links = x.getElementsByTagName("a");
        for (var i = 1; i < links.length; i++) {
            if (links[i].style.display === "block") {
                links[i].style.display = "none";
            } else {
                links[i].style.display = "block";
            }
        }
    }

    function loadContent(url) {
		$('#loader').show(); // Mostrar o loader
        $.ajax({
            url: url,
            success: function(data) {
                $('#content').html(data);
				$('#loader').hide(); // Esconder o loader
            },
            error: function() {
                $('#content').html('<p>Erro ao carregar o conteúdo.</p>');
				 $('#loader').hide(); // Esconder o loader
            }
        });
    }
</script>

</body>
</html>
