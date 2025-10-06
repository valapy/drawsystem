<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sorteo: {{ $draw->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .draw-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .draw-container {
            position: relative;
            width: 100%;
            height: 100vh;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            @if ($draw->background_image) background-image: url('{{ asset('storage/'.$draw->background_image) }}');
            @else background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            @endif
        }
    </style>
</head>

<body>
    <div class="draw-container">

        <div class="winners-container">
            <div class="counter">
                Ganadores: <span id="winners-count">{{ $draw->winners()->count() }}</span> / {{ $draw->participants()->count() }}
            </div>

            <h4 style="font-size: 1.1rem; margin-bottom: 10px; color: #333;">Lista de Ganadores:</h4>
            <ul id="winners-list" class="winners-list-side">
                @foreach ($draw->winners as $winner)
                <li>{{ $winner->participant->display_value }}</li>
                @endforeach
            </ul>
        </div>
        <div class="content">
            <h1 class="title">{{ $draw->name }}</h1>

            <div class="display-box">
                <div class="participant-name" id="participant-display">
                    ¡Comenzar el sorteo!
                </div>
            </div>

            <button class="draw-button" id="draw-button" onclick="toggleDraw()">
                SORTEAR
            </button>
        </div>
    </div>

    <script>
        let isShuffling = false;
        let shuffleInterval = null;
        let participants = [];
        let currentIndex = 0;
        const displayElement = document.getElementById('participant-display');
        const buttonElement = document.getElementById('draw-button');

        // 👇 3. JAVASCRIPT APUNTANDO A LOS NUEVOS ELEMENTOS
        const winnersCountElement = document.getElementById('winners-count');
        const winnersListElement = document.getElementById('winners-list');

        // Cargar participantes al inicio
        async function loadParticipants() {
            try {
                const response = await fetch('{{ route('draws.participants', $draw) }}');
                const data = await response.json();
                participants = data;

                if (participants.length === 0) {
                    displayElement.textContent = 'No hay más participantes';
                    buttonElement.disabled = true;
                }
            } catch (error) {
                console.error('Error cargando participantes:', error);
            }
        }

        function toggleDraw() {
            if (!isShuffling) {
                startShuffle();
            } else {
                stopShuffle();
            }
        }

        function startShuffle() {
            if (participants.length === 0) return;

            isShuffling = true;
            buttonElement.textContent = 'DETENER';
            displayElement.classList.add('shuffling');
            displayElement.classList.remove('winner');

            shuffleInterval = setInterval(() => {
                currentIndex = Math.floor(Math.random() * participants.length);
                displayElement.textContent = participants[currentIndex].display_value;
            }, 20);
        }

        async function stopShuffle() {
            if (!shuffleInterval) return;

            clearInterval(shuffleInterval);
            isShuffling = false;
            buttonElement.textContent = 'SORTEAR';
            buttonElement.disabled = true;

            try {
                const response = await fetch('{{ route('draws.perform', $draw) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.winner) {
                    displayElement.textContent = data.winner.display_value;
                    displayElement.classList.remove('shuffling');
                    displayElement.classList.add('winner');

                    // Actualizar contador
                    winnersCountElement.textContent = parseInt(winnersCountElement.textContent) + 1;

                    // Añadir ganador a la lista
                    const listItem = document.createElement('li');
                    listItem.textContent = data.winner.display_value;
                    winnersListElement.appendChild(listItem);
                    winnersListElement.scrollTop = winnersListElement.scrollHeight; // Auto-scroll

                    createConfetti();
                    await loadParticipants();
                    buttonElement.disabled = false;
                } else {
                    displayElement.textContent = 'Sorteo finalizado';
                    buttonElement.disabled = true;
                }
            } catch (error) {
                console.error('Error realizando sorteo:', error);
                displayElement.textContent = 'Error al sortear';
                buttonElement.disabled = false;
            }
        }

        function createConfetti() {
            const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722'];

            for (let i = 0; i < 100; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-10px';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 0.3 + 's';
                    confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    document.body.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 2000);
                }, i * 20);
            }
        }

        loadParticipants();

        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' && !buttonElement.disabled) {
                e.preventDefault();
                toggleDraw();
            }
        });
    </script>
</body>

</html>
