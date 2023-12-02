<?php
class Conexao
{
    private static $instance;

    public static function getConexao()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PDO('mysql:host=localhost;dbname=assinar', 'root', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
        }

        return self::$instance;
    }
}

// Processar o formulário e inserir no banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $assinatura = $_POST['assinatura'];

    // Verificar tamanho mínimo da assinatura (ajuste conforme necessário)
    $tamanhoMinimoAssinatura = 1000; // Defina o tamanho mínimo desejado em bytes

    // Converte a string base64 da assinatura em dados binários e verifica o tamanho
    $assinaturaBinaria = base64_decode(str_replace('data:image/png;base64,', '', $assinatura));
    if (strlen($assinaturaBinaria) < $tamanhoMinimoAssinatura) {
        echo "Erro: Assinatura muito pequena. Certifique-se de assinar corretamente.";
        exit;
    }

    try {
        $conexao = Conexao::getConexao();

        $sql = "INSERT INTO assinaturas (nome, assinatura) VALUES (:nome, :assinatura)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':assinatura', $assinatura, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "Assinatura registrada com sucesso!";
        } else {
            echo "Erro ao registrar a assinatura.";
        }
    } catch (PDOException $e) {
        echo "Erro na conexão com o banco de dados: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Digital</title>
    <link rel="stylesheet" type="text/css" href="signature_pad-master/docs/css/signature-pad.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        canvas {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        button {
            background-color: #4caf50;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        button.registrados-btn {
            background-color: #008CBA;
        }

        button.registrados-btn:hover {
            background-color: #006080;
        }

        .erro-assinatura {
            color: red;
            margin-top: 10px;
        }

        @media (max-width: 600px) {
            form {
                width: 90%;
            }
        }
    </style>
</head>
<body>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <label for="nome">Nome:</label>
        <input type="text" name="nome" required>

        <label for="assinatura">Assinatura:</label>
        <canvas id="assinaturaCanvas" width="400" height="200"></canvas>
        <input type="hidden" name="assinatura" id="assinaturaInput" required>

        <span class="erro-assinatura" id="erroAssinatura"></span> <!-- Elemento para exibir mensagem de erro -->
        <br>
        <button type="submit" onclick="return validarAssinatura()">Salvar Assinatura</button> 
        <button type="button" id="limparAssinaturaBtn">Limpar Assinatura</button>

        <br> <br>
        
    <button class="registrados-btn" onclick="window.location.href='novo/tabela.php'">Registrados</button>
    </form>


    <script src="signature_pad-master/docs/js/signature_pad.umd.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var canvas = document.getElementById('assinaturaCanvas');
        var signaturePad = new SignaturePad(canvas);

        // Adiciona um ouvinte de evento para limpar a assinatura ao clicar em um botão de reset
        document.getElementById('limparAssinaturaBtn').addEventListener('click', function (e) {
            e.preventDefault();
            signaturePad.clear();
        });

        // Adiciona um ouvinte de evento para validar a assinatura antes de enviar o formulário
        document.querySelector('form').addEventListener('submit', function (e) {
            // Verifica se a assinatura é válida (área não é muito pequena)
            if (getSignatureArea(signaturePad) < 1000) {
                alert("A assinatura é muito pequena. Por favor, assine novamente.");
                e.preventDefault(); // Impede o envio do formulário se a assinatura for muito pequena
            } else {
                // Atualiza o campo de input com os dados da assinatura antes de enviar o formulário.
                document.getElementById('assinaturaInput').value = signaturePad.toDataURL();
            }
        });

        // Função para calcular a área da assinatura
        function getSignatureArea(pad) {
            var ctx = pad._ctx;
            var pixels = ctx.getImageData(0, 0, pad.canvas.width, pad.canvas.height).data;
            var area = 0;

            for (var i = 3; i < pixels.length; i += 4) {
                if (pixels[i] !== 0) {
                    area++;
                }
            }

            return area;
        }
    });
</script>



</body>
</html>
