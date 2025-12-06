document.addEventListener('DOMContentLoaded', () => {
    const addTextBtn = document.getElementById('add-text-btn');
    const canvas = document.getElementById('preview');
    const form = document.getElementById('presentation-form');

    // --- FUNCIÓN PARA ACTUALIZAR LA POSICIÓN EN LOS INPUTS OCULTOS ---
    function updateHiddenInputs(index, top, left) {
        document.getElementById(`texto-${index}-top`).value = top;
        document.getElementById(`texto-${index}-left`).value = left;
    }

    // --- FUNCIÓN PARA DRAG & DROP ---
    const dragElement = (elmnt, index) => {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

        const closeDragElement = () => {
            document.onmouseup = null;
            document.onmousemove = null;
        };

        const elementDrag = (e) => {
            e = e || window.event;

            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;

            let newTop = elmnt.offsetTop - pos2;
            let newLeft = elmnt.offsetLeft - pos1;

            newTop = Math.max(0, Math.min(newTop, canvas.clientHeight - elmnt.offsetHeight));
            newLeft = Math.max(0, Math.min(newLeft, canvas.clientWidth - elmnt.offsetWidth));

            elmnt.style.top = newTop + "px";
            elmnt.style.left = newLeft + "px";

            // ACTUALIZAR INPUTS OCULTOS PARA PHP
            updateHiddenInputs(index, newTop, newLeft);
        };

        const dragMouseDown = (e) => {
            e = e || window.event;

            // Iniciar arrastre
            pos3 = e.clientX;
            pos4 = e.clientY;

            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;

            e.stopPropagation();
        };

        elmnt.onmousedown = dragMouseDown;
    };

    // --- AÑADIR UN NUEVO TEXTO ---
    addTextBtn.addEventListener('click', () => {
        const index = document.querySelectorAll('.draggable-text').length;

        // Crear textarea
        const newTextArea = document.createElement('textarea');
        newTextArea.className = 'draggable-text';
        newTextArea.placeholder = 'Escribe tu texto aquí...';

        // ASIGNAR NOMBRE PARA PHP
        newTextArea.name = `textos[${index}][texto]`;

        // Posicionar centrado
        const canvasRect = canvas.getBoundingClientRect();
        const initialWidth = 200;
        const initialHeight = 100;

        const startTop = canvasRect.height / 2 - initialHeight / 2;
        const startLeft = canvasRect.width / 2 - initialWidth / 2;

        newTextArea.style.width = `${initialWidth}px`;
        newTextArea.style.height = `${initialHeight}px`;

        newTextArea.style.top = `${startTop}px`;
        newTextArea.style.left = `${startLeft}px`;

        canvas.appendChild(newTextArea);

        // --- CREAR INPUTS OCULTOS PARA PHP ---
        const inputTop = document.createElement('input');
        inputTop.type = "hidden";
        inputTop.id = `texto-${index}-top`;
        inputTop.name = `textos[${index}][top]`;
        inputTop.value = startTop;

        const inputLeft = document.createElement('input');
        inputLeft.type = "hidden";
        inputLeft.id = `texto-${index}-left`;
        inputLeft.name = `textos[${index}][left]`;
        inputLeft.value = startLeft;

        form.appendChild(inputTop);
        form.appendChild(inputLeft);

        // Hacer el nuevo textarea arrastrable
        dragElement(newTextArea, index);

        newTextArea.focus();
    });
})
