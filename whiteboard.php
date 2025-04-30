<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Whiteboard - ExLence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --secondary: #5C6BC0;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-button {
            text-decoration: none;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .toolbar {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .tool-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tool-btn:hover {
            background: var(--primary);
            color: white;
        }

        .tool-btn.active {
            background: var(--primary);
            color: white;
        }

        .color-picker {
            width: 40px;
            height: 40px;
            padding: 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .size-slider {
            width: 100px;
        }

        .whiteboard-container {
            flex: 1;
            padding: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #e9ecef;
        }

        #whiteboard {
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            touch-action: none;
        }

        .action-buttons {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 1rem;
            border: none;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.2);
        }

        .save-btn {
            background: #2ecc71;
        }

        .clear-btn {
            background: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        <div class="toolbar">
            <button class="tool-btn active" id="pencil">
                <i class="fas fa-pencil-alt"></i>
                Pencil
            </button>
            <button class="tool-btn" id="eraser">
                <i class="fas fa-eraser"></i>
                Eraser
            </button>
            <button class="tool-btn" id="rectangle">
                <i class="fas fa-square"></i>
                Rectangle
            </button>
            <button class="tool-btn" id="circle">
                <i class="fas fa-circle"></i>
                Circle
            </button>
            <button class="tool-btn" id="line">
                <i class="fas fa-minus"></i>
                Line
            </button>
            <button class="tool-btn" id="text">
                <i class="fas fa-font"></i>
                Text
            </button>
            <input type="color" class="color-picker" id="colorPicker" value="#000000">
            <input type="range" class="size-slider" id="sizeSlider" min="1" max="50" value="5">
            <button class="tool-btn" id="undo">
                <i class="fas fa-undo"></i>
                Undo
            </button>
            <button class="tool-btn" id="redo">
                <i class="fas fa-redo"></i>
                Redo
            </button>
        </div>
    </div>

    <div class="whiteboard-container">
        <canvas id="whiteboard"></canvas>
    </div>

    <div class="action-buttons">
        <button class="action-btn clear-btn" id="clearBtn">
            <i class="fas fa-trash"></i>
        </button>
        <button class="action-btn save-btn" id="saveBtn">
            <i class="fas fa-download"></i>
        </button>
    </div>

    <script>
        const canvas = document.getElementById('whiteboard');
        const ctx = canvas.getContext('2d');
        const colorPicker = document.getElementById('colorPicker');
        const sizeSlider = document.getElementById('sizeSlider');
        const clearBtn = document.getElementById('clearBtn');
        const saveBtn = document.getElementById('saveBtn');
        const tools = document.querySelectorAll('.tool-btn');

        // Set canvas size
        function resizeCanvas() {
            const container = document.querySelector('.whiteboard-container');
            canvas.width = container.clientWidth - 40;
            canvas.height = container.clientHeight - 40;
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Drawing state
        let isDrawing = false;
        let currentTool = 'pencil';
        let lastX = 0;
        let lastY = 0;
        let undoStack = [];
        let redoStack = [];
        let startX, startY;
        let isDrawingShape = false;
        let tempCanvas = document.createElement('canvas');
        let tempCtx = tempCanvas.getContext('2d');

        // Tool selection
        tools.forEach(tool => {
            tool.addEventListener('click', () => {
                tools.forEach(t => t.classList.remove('active'));
                tool.classList.add('active');
                currentTool = tool.id;
            });
        });

        // Drawing functions
        function draw(e) {
            if (!isDrawing) return;

            ctx.lineWidth = sizeSlider.value;
            ctx.lineCap = 'round';
            ctx.strokeStyle = currentTool === 'eraser' ? '#ffffff' : colorPicker.value;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();

            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // Mouse events
        canvas.addEventListener('mousedown', (e) => {
            if (['rectangle', 'circle', 'line'].includes(currentTool)) {
                startDrawingShape(e);
            } else {
                isDrawing = true;
                [lastX, lastY] = [e.offsetX, e.offsetY];
            }
        });

        canvas.addEventListener('mousemove', (e) => {
            if (['rectangle', 'circle', 'line'].includes(currentTool)) {
                drawShape(e);
            } else {
                draw(e);
            }
        });

        canvas.addEventListener('mouseup', () => {
            if (['rectangle', 'circle', 'line'].includes(currentTool)) {
                finishDrawingShape();
            } else {
                isDrawing = false;
                saveState();
            }
        });

        // Touch events
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            [lastX, lastY] = [
                touch.clientX - rect.left,
                touch.clientY - rect.top
            ];
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            const offsetX = touch.clientX - rect.left;
            const offsetY = touch.clientY - rect.top;

            ctx.lineWidth = sizeSlider.value;
            ctx.lineCap = 'round';
            ctx.strokeStyle = currentTool === 'eraser' ? '#ffffff' : colorPicker.value;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(offsetX, offsetY);
            ctx.stroke();

            [lastX, lastY] = [offsetX, offsetY];
        });

        canvas.addEventListener('touchend', () => isDrawing = false);

        // Clear canvas
        clearBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to clear the whiteboard?')) {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
        });

        // Save canvas
        saveBtn.addEventListener('click', () => {
            const link = document.createElement('a');
            link.download = 'whiteboard.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        // Initialize white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        function saveState() {
            undoStack.push(canvas.toDataURL());
            redoStack = [];
        }

        function undo() {
            if (undoStack.length > 0) {
                redoStack.push(canvas.toDataURL());
                let imgData = undoStack.pop();
                let img = new Image();
                img.src = imgData;
                img.onload = function() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0);
                }
            }
        }

        function redo() {
            if (redoStack.length > 0) {
                undoStack.push(canvas.toDataURL());
                let imgData = redoStack.pop();
                let img = new Image();
                img.src = imgData;
                img.onload = function() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0);
                }
            }
        }

        document.getElementById('undo').addEventListener('click', undo);
        document.getElementById('redo').addEventListener('click', redo);

        function drawShape(e) {
            if (!isDrawingShape) return;

            const currentX = e.offsetX;
            const currentY = e.offsetY;

            // Clear the temporary canvas
            tempCtx.clearRect(0, 0, canvas.width, canvas.height);
            tempCtx.drawImage(canvas, 0, 0);

            tempCtx.beginPath();
            tempCtx.strokeStyle = colorPicker.value;
            tempCtx.lineWidth = sizeSlider.value;

            switch(currentTool) {
                case 'rectangle':
                    tempCtx.rect(startX, startY, currentX - startX, currentY - startY);
                    break;
                case 'circle':
                    const radius = Math.sqrt(Math.pow(currentX - startX, 2) + Math.pow(currentY - startY, 2));
                    tempCtx.arc(startX, startY, radius, 0, 2 * Math.PI);
                    break;
                case 'line':
                    tempCtx.moveTo(startX, startY);
                    tempCtx.lineTo(currentX, currentY);
                    break;
            }
            tempCtx.stroke();
        }

        function startDrawingShape(e) {
            isDrawingShape = true;
            startX = e.offsetX;
            startY = e.offsetY;
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            tempCtx.drawImage(canvas, 0, 0);
        }

        function finishDrawingShape() {
            if (!isDrawingShape) return;
            isDrawingShape = false;
            ctx.drawImage(tempCanvas, 0, 0);
            saveState();
        }

        // Text tool implementation
        canvas.addEventListener('click', (e) => {
            if (currentTool === 'text') {
                const text = prompt('Enter text:');
                if (text) {
                    ctx.font = `${sizeSlider.value}px Arial`;
                    ctx.fillStyle = colorPicker.value;
                    ctx.fillText(text, e.offsetX, e.offsetY);
                    saveState();
                }
            }
        });

        // Initialize
        saveState(); // Save initial blank state
    </script>
</body>
</html> 