<!-- http://simon-test-romane.wuaze.com/ -->
<!-- https://cpanel.infinityfree.com/panel/indexpl.php -->
<!-- https://openclassrooms.com/fr/courses/918836-concevez-votre-site-web-avec-php-et-mysql/914508-ajoutez-modifiez-et-supprimez-des-recettes -->

<?php
try {
  $mysqlClient = new PDO('mysql:host=sql306.infinityfree.com;dbname=if0_36054392_spend_data', 'if0_36054392', 'Wy0tnfGrn6jFN');
} catch (Exception $e) {
  die('Erreur : ' . $e->getMessage());
}

if (count($_POST) > 0) {
  if (isset($_POST['delete']) && $_POST['delete'] === "true") {
    $req = $mysqlClient->prepare('DELETE FROM spend WHERE id = :id');
    $req->execute(array(
      'id' => $_POST['id']
    ));
  } else {
    $date = ($_POST['date'] !== "") ? $_POST['date'] : date('Y-m-d');
    $amount = $_POST['amount'];
    $categorie = $_POST['categorie'];
    $devise = $_POST['devise'];
    $description = $_POST['description'];
    $req = $mysqlClient->prepare('INSERT INTO spend(nom, categorie, date, montant, devise, time_ajout) VALUES(:nom, :categorie, :date, :amount, :devise, NOW())');
    $req->execute(array(
      'date' => $date,
      'amount' => $amount,
      'nom' => $description,
      'categorie' => $categorie,
      'devise' => $devise
    ));
  }

  header("HTTP/1.1 303 See Other");
  header("Location: index.php");
  die();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dépenses Mexique</title>
  <link rel="icon" type="image/x-icon" href="romane.ico" />
  <link rel="stylesheet" href="styleindex.css" />
  <script>
    function click_line(id) {
      var xhttp = new XMLHttpRequest();
      xhttp.open("POST", "index.php", true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send("delete=true&id=" + id);
      window.location.reload()
    }
  </script>
</head>

<body>
  <a href="salutromane.html">Salut Romane viens par ici</a> <br />
  <main>
    <form action="index.php" method="post">
      <h2>Enregistrement des dépenses</h2>
      <div>
        <label for="description">Description : </label>
        <input type="text" name="description" id="description" maxlength="50" />
      </div>
      <div>
        <label for="categorie">Catégorie : </label>
        <select name="categorie" id="categorie" required>
          <option value="quotidien" selected>Quotidien</option>
          <option value="loisir">Loisirs</option>
          <option value="frais">Frais</option>
          <option value="retrait">Retrait</option>
        </select>
      </div>
      <div id="saisie_montant">
        <label for="amount">Montant* : </label>
        <input type="number" name="amount" id="amount" min="0" step="0.01" max="4000000000" autofocus required />
        <select name="devise" id="devise" required>
          <option value="euros" selected>€ euros</option>
          <option value="pesos">MXN pesos</option>
          <option value="dollars">$ dollars</option>
        </select>
      </div>
      <div>
        <label for="date">Date : </label>
        <input type="date" name="date" id="date" />
      </div>
      <div>
        <input type="submit" value="Ajouter" />
      </div>
    </form>
    <section id="resume">
      <h2>Résumé</h2>
      <?php
      $url_devise = 'https://api.exchangerate-api.com/v4/latest/EUR';
      $json = file_get_contents($url_devise);
      $data = json_decode($json, true);
      $conversion['euros'] = 1;
      $conversion['pesos'] = round(1/$data['rates']['MXN'], 2);
      $conversion['dollars'] = round(1/$data['rates']['USD'], 2);

      $reponse = $mysqlClient->query('SELECT devise, SUM(montant) AS total FROM spend GROUP BY devise');
      $total = 0;
      foreach ($reponse as $row) {
        $total += $row['total'] * $conversion[$row['devise']];
      }

      $donnees = $reponse->fetch();
      echo '<p>Total des dépenses : ' . $total . ' €</p>';
      $reponse->closeCursor();
      echo "<p>";
      $totaux = [];
      foreach ($mysqlClient->query('SELECT categorie, devise, SUM(montant) AS total FROM spend GROUP BY categorie, devise') as $row) {
        if (!isset($total[$row['categorie']])) {
          $totaux[$row['categorie']] = 0;
        }
        $totaux[$row['categorie']] += $row['total'] * $conversion[$row['devise']];
      }
      foreach ($totaux as $categorie => $total) {
        echo 'Total des dépenses pour la catégorie <strong>' . $categorie . '</strong> : ' . $total . ' €<br />';
      }
      echo "</p>";
      ?>
    </section>
    <section id="depenses_section">
      <h2>Dépenses</h2>
      <table id="table_depenses">
        <tr id="header_line">
          <th>Date</th>
          <th>Description</th>
          <th>Catégorie</th>
          <th>Montant</th>
          <th>Devise</th>
        </tr>
        <?php
        $reponse = $mysqlClient->query('SELECT * FROM spend ORDER BY date DESC, time_ajout DESC');
        while ($donnees = $reponse->fetch()) {
          echo '<tr onclick="click_line(' . $donnees['id'] . ')">';
          echo '<td>' . $donnees['date'] . '</td>';
          echo '<td class="colonne_description">' . $donnees['nom'] . '</td>';
          echo '<td>' . $donnees['categorie'] . '</td>';
          echo '<td>' . $donnees['montant'] . '</td>';
          echo '<td>' . $donnees['devise'] . '</td>';
          echo '</tr>';
        }
        $reponse->closeCursor();
        ?>
      </table>
    </section>
  </main>
</body>

</html>