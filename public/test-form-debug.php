<!DOCTYPE html>
<html>
<head>
    <title>Form Debug Test</title>
</head>
<body>
    <h2>Form Debug Test</h2>
    
    <form method="POST" action="/projetweb2/public/auctions/create">
        <div>
            <label>Nom du Timbre:</label>
            <input type="text" name="nom" value="Debug Test" required>
        </div>
        
        <div>
            <label>Pays d'Origine:</label>
            <select name="pays_origine" required>
                <option value="Canada">Canada</option>
            </select>
        </div>
        
        <div>
            <label>Prix de Départ:</label>
            <input type="number" name="prix_plancher" value="20" required>
        </div>
        
        <div>
            <label>Date de Fin:</label>
            <input type="datetime-local" name="date_fin" required>
        </div>
        
        <button type="submit">Submit Debug Form</button>
    </form>
    
    <script>
        // Set default date (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.querySelector('input[name="date_fin"]').value = tomorrow.toISOString().slice(0, 16);
        
        // Add form submission logging
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submitted!');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            
            // Log form data
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });
    </script>
</body>
</html>
