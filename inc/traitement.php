<?php
// Assurez-vous d'avoir configuré une connexion à la base de données ici
// Par exemple: $conn = new mysqli($servername, $username, $password, $dbname);

if (isset($_POST["nom"])) {
    $nom = $_POST["nom"];
    
    // Protection contre les injections SQL
    $stmt = $conn->prepare("SELECT * FROM ma_table WHERE nom = ?");
    $stmt->bind_param("s", $nom); 
    $stmt->execute();
    $result = $stmt->get_result();

    // Traitement des résultats
    while ($row = $result->fetch_assoc()) {
        echo "Nom: " . $row["nom"] . "<br>";
    }
}
?>
