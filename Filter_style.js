function filterByStyle() {
    const style = document.getElementById("style-select").value;
    
    // Effectuer une requête AJAX vers le script PHP
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "filter_tableaux.php?style=" + encodeURIComponent(style), true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("tableaux-container").innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}


