<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scientific Calculator - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
        }

        .header {
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button {
            text-decoration: none;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .calculator-container {
            max-width: 400px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        #display {
            width: 100%;
            height: 80px;
            margin-bottom: 1rem;
            font-size: 2rem;
            text-align: right;
            padding: 0.5rem;
            border: 2px solid #eee;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .buttons {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }

        button {
            padding: 1rem;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: var(--primary);
            color: white;
        }

        .operator {
            background: #e3f2fd;
            color: var(--primary);
        }

        .equals {
            background: var(--primary);
            color: white;
            grid-column: span 2;
        }

        .function {
            background: #f3e5f5;
            color: #9c27b0;
        }

        .memory {
            background: #fce4ec;
            color: #e91e63;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <div class="calculator-container">
        <input type="text" id="display" readonly>
        <div class="buttons">
            <!-- Memory Functions -->
            <button class="memory" onclick="memoryStore()">MS</button>
            <button class="memory" onclick="memoryRecall()">MR</button>
            <button class="memory" onclick="memoryAdd()">M+</button>
            <button class="memory" onclick="memorySub()">M-</button>
            <button class="memory" onclick="memoryClear()">MC</button>

            <!-- Scientific Functions -->
            <button class="function" onclick="calculate('sin')">sin</button>
            <button class="function" onclick="calculate('cos')">cos</button>
            <button class="function" onclick="calculate('tan')">tan</button>
            <button class="function" onclick="calculate('sqrt')">√</button>
            <button class="function" onclick="calculate('log')">log</button>

            <button class="function" onclick="calculate('pow2')">x²</button>
            <button class="function" onclick="calculate('pow3')">x³</button>
            <button class="function" onclick="calculate('exp')">exp</button>
            <button class="function" onclick="calculate('pi')">π</button>
            <button class="function" onclick="calculate('e')">e</button>

            <!-- Numbers and Basic Operators -->
            <button onclick="appendNumber(7)">7</button>
            <button onclick="appendNumber(8)">8</button>
            <button onclick="appendNumber(9)">9</button>
            <button class="operator" onclick="appendOperator('/')">/</button>
            <button onclick="clearDisplay()">C</button>

            <button onclick="appendNumber(4)">4</button>
            <button onclick="appendNumber(5)">5</button>
            <button onclick="appendNumber(6)">6</button>
            <button class="operator" onclick="appendOperator('*')">×</button>
            <button onclick="backspace()">⌫</button>

            <button onclick="appendNumber(1)">1</button>
            <button onclick="appendNumber(2)">2</button>
            <button onclick="appendNumber(3)">3</button>
            <button class="operator" onclick="appendOperator('-')">-</button>
            <button onclick="toggleSign()">±</button>

            <button onclick="appendNumber(0)">0</button>
            <button onclick="appendDecimal()">.</button>
            <button class="equals" onclick="calculateResult()">=</button>
            <button class="operator" onclick="appendOperator('+')">+</button>
        </div>
    </div>

    <script>
        let memory = 0;
        let display = document.getElementById('display');

        function appendNumber(num) {
            display.value += num;
        }

        function appendOperator(op) {
            display.value += op;
        }

        function appendDecimal() {
            if (!display.value.includes('.')) {
                display.value += '.';
            }
        }

        function clearDisplay() {
            display.value = '';
        }

        function backspace() {
            display.value = display.value.slice(0, -1);
        }

        function toggleSign() {
            if (display.value !== '') {
                display.value = -parseFloat(display.value);
            }
        }

        function calculate(func) {
            let num = parseFloat(display.value);
            switch(func) {
                case 'sin':
                    display.value = Math.sin(num * Math.PI / 180);
                    break;
                case 'cos':
                    display.value = Math.cos(num * Math.PI / 180);
                    break;
                case 'tan':
                    display.value = Math.tan(num * Math.PI / 180);
                    break;
                case 'sqrt':
                    display.value = Math.sqrt(num);
                    break;
                case 'log':
                    display.value = Math.log10(num);
                    break;
                case 'pow2':
                    display.value = Math.pow(num, 2);
                    break;
                case 'pow3':
                    display.value = Math.pow(num, 3);
                    break;
                case 'exp':
                    display.value = Math.exp(num);
                    break;
                case 'pi':
                    display.value = Math.PI;
                    break;
                case 'e':
                    display.value = Math.E;
                    break;
            }
        }

        function calculateResult() {
            try {
                display.value = eval(display.value);
            } catch (error) {
                display.value = 'Error';
            }
        }

        // Memory functions
        function memoryStore() {
            memory = parseFloat(display.value) || 0;
        }

        function memoryRecall() {
            display.value = memory;
        }

        function memoryAdd() {
            memory += parseFloat(display.value) || 0;
        }

        function memorySub() {
            memory -= parseFloat(display.value) || 0;
        }

        function memoryClear() {
            memory = 0;
        }

        // Keyboard support
        document.addEventListener('keydown', (event) => {
            const key = event.key;
            if (/[0-9]/.test(key)) {
                appendNumber(parseInt(key));
            } else if (['+', '-', '*', '/'].includes(key)) {
                appendOperator(key);
            } else if (key === '.') {
                appendDecimal();
            } else if (key === 'Enter') {
                calculateResult();
            } else if (key === 'Backspace') {
                backspace();
            } else if (key === 'Escape') {
                clearDisplay();
            }
        });
    </script>
</body>
</html> 