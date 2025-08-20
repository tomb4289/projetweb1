<!DOCTYPE html>
<html>
<head>
    <title>Test Form Submission</title>
</head>
<body>
    <h2>Test Form Submission</h2>
    
    <form method="POST" action="/projetweb1/public/auctions/create">
        <div>
            <label>Nom du Timbre:</label>
            <input type="text" name="nom" value="Test Timbre 2" required>
        </div>
        
        <div>
            <label>Pays d'Origine:</label>
            <select name="pays_origine" required>
                <option value="Canada">Canada</option>
                <option value="France">France</option>
            </select>
        </div>
        
        <div>
            <label>Condition:</label>
            <select name="condition">
                <option value="Bonne">Bonne</option>
                <option value="Excellente">Excellente</option>
            </select>
        </div>
        
        <div>
            <label>Prix de Départ:</label>
            <input type="number" name="prix_plancher" value="15" step="0.01" required>
        </div>
        
        <div>
            <label>Date de Fin:</label>
            <input type="datetime-local" name="date_fin" required>
        </div>
        
        <button type="submit">Submit Test Form</button>
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
            console.log('Form data:', new FormData(this));
        });
    </script>
</body>
</html>
