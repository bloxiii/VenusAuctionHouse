function filterByStyle($var) {
  console.log("je suis dans filter by style");
  const style = document.getElementById("style-select").value;
  console.log("style", style);

  // Effectuer une requête AJAX vers le script PHP
  /*const xhr = new XMLHttpRequest();
  xhr.open("GET", "des_oeuvres.php?style=" + encodeURIComponent(style), true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      document.getElementById("style-select").innerHTML = xhr.responseText;
    }   
  };        
  xhr.send();*/

  $var = style;
}
