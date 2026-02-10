<x-dashboard-layout>
    <x-slot name="header">
        <a href="{{ route('dashboard.replays', $project) }}" class="text-gray-400 hover:text-gray-600">Session Replays</a>
        <span class="mx-2 text-gray-300">/</span>
        <span>{{ Str::limit($replay->session_id, 20) }}</span>
    </x-slot>
    <x-slot name="title">Replay Player - {{ $project->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Player -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-[9/16] sm:aspect-[3/4] max-h-[600px] bg-gray-900 relative" x-data="replayPlayer()" x-init="init()">
                    <!-- Wireframe Viewport -->
                    <div id="replay-viewport" class="absolute inset-0 overflow-hidden">
                        <!-- Wireframe will be rendered here -->
                        <div class="flex items-center justify-center h-full text-gray-500">
                            <p class="text-sm">Loading replay...</p>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                        <div class="flex items-center gap-4">
                            <button @click="togglePlay()" class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
                                <svg x-show="!playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <svg x-show="playing" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                </svg>
                            </button>
                            <div class="flex-1">
                                <input type="range" x-model="progress" @input="seek($event.target.value)" class="w-full h-1 bg-white/20 rounded-full appearance-none cursor-pointer" min="0" max="100">
                            </div>
                            <span class="text-white text-sm font-mono" x-text="currentTime + ' / ' + totalTime"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Info -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Session Info</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">User ID</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $replay->user_id ?? 'Anonymous' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Session ID</dt>
                        <dd class="mt-1 text-xs font-mono text-gray-600 break-all">{{ $replay->session_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Started</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $replay->started_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                    @if($replay->ended_at)
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Duration</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ gmdate('H:i:s', $replay->duration_seconds ?? 0) }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Frames</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $replay->frames->count() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Frame Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Frame Timeline</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($replay->frames as $index => $frame)
                    <button class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors text-sm frame-btn" data-frame="{{ $index }}">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">Frame {{ $frame->chunk_index }}</span>
                            <span class="text-xs text-gray-400">{{ $frame->frame_type }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $frame->timestamp->format('H:i:s') }}</div>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        const frames = @json($replay->frames->map(fn($f) => ['wireframe' => $f->wireframe, 'timestamp' => $f->timestamp]));

        function replayPlayer() {
            return {
                playing: false,
                progress: 0,
                currentFrame: 0,
                interval: null,
                currentTime: '0:00',
                totalTime: '0:00',

                init() {
                    if (frames.length > 0) {
                        this.renderFrame(0);
                        this.calculateTotalTime();
                    }
                },

                togglePlay() {
                    this.playing = !this.playing;
                    if (this.playing) {
                        this.play();
                    } else {
                        this.pause();
                    }
                },

                play() {
                    this.interval = setInterval(() => {
                        if (this.currentFrame < frames.length - 1) {
                            this.currentFrame++;
                            this.progress = (this.currentFrame / (frames.length - 1)) * 100;
                            this.renderFrame(this.currentFrame);
                            this.updateCurrentTime();
                        } else {
                            this.pause();
                            this.currentFrame = 0;
                            this.progress = 0;
                        }
                    }, 500);
                },

                pause() {
                    this.playing = false;
                    clearInterval(this.interval);
                },

                seek(value) {
                    this.currentFrame = Math.round((value / 100) * (frames.length - 1));
                    this.renderFrame(this.currentFrame);
                    this.updateCurrentTime();
                },

                renderFrame(index) {
                    const viewport = document.getElementById('replay-viewport');
                    const frame = frames[index];
                    if (frame && frame.wireframe) {
                        viewport.innerHTML = this.buildWireframe(frame.wireframe);
                    }
                },

                buildWireframe(node, depth = 0) {
                    if (!node) return '';
                    
                    const style = `
                        position: absolute;
                        left: ${node.frame?.x || 0}px;
                        top: ${node.frame?.y || 0}px;
                        width: ${node.frame?.width || 0}px;
                        height: ${node.frame?.height || 0}px;
                        opacity: ${node.alpha || 1};
                        ${node.hidden ? 'display: none;' : ''}
                    `;
                    
                    let classes = 'border border-gray-600 bg-gray-800/50';
                    let content = '';
                    
                    if (node.text) {
                        content = `<span class="text-white text-xs p-1 truncate block">${node.text}</span>`;
                    }
                    if (node.title) {
                        content = `<span class="text-blue-400 text-xs p-1 truncate block">${node.title}</span>`;
                    }
                    
                    let children = '';
                    if (node.children) {
                        children = node.children.map(c => this.buildWireframe(c, depth + 1)).join('');
                    }
                    
                    return `<div style="${style}" class="${classes}" title="${node.type || ''}">${content}${children}</div>`;
                },

                calculateTotalTime() {
                    if (frames.length > 0) {
                        const duration = frames.length * 0.5;
                        this.totalTime = this.formatTime(duration);
                    }
                },

                updateCurrentTime() {
                    const current = this.currentFrame * 0.5;
                    this.currentTime = this.formatTime(current);
                },

                formatTime(seconds) {
                    const mins = Math.floor(seconds / 60);
                    const secs = Math.floor(seconds % 60);
                    return `${mins}:${secs.toString().padStart(2, '0')}`;
                }
            }
        }

        // Frame button clicks
        document.querySelectorAll('.frame-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.frame);
                Alpine.store('replayPlayer')?.seek((index / (frames.length - 1)) * 100);
            });
        });
    </script>
</x-dashboard-layout>

