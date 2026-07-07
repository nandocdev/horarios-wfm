<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Panel Lateral: Árbol de Directorios -->
    <aside class="lg:col-span-3 space-y-6">
        <flux:card class="p-4 bg-zinc-50/50 dark:bg-zinc-900/30">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500 font-bold">Explorar</flux:heading>
                <flux:modal.trigger name="create-folder">
                    <flux:button icon="folder-plus" variant="ghost" size="sm" inset />
                </flux:modal.trigger>
            </div>

            <nav class="space-y-1">
                <button wire:click="$set('viewMode', 'my_files'); navigateTo(null)" 
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-opacity {{ !$currentFolderId && $viewMode === 'my_files' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    <flux:icon name="home" variant="mini" />
                    Mis Archivos
                </button>
                
                <button wire:click="$set('viewMode', 'shared')" 
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-opacity {{ $viewMode === 'shared' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    <flux:icon name="users" variant="mini" />
                    Compartidos
                </button>

                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="xs" class="mb-2 px-3 text-zinc-400 uppercase font-bold text-[10px]">Carpetas</flux:heading>
                    <div class="space-y-0.5">
                        @foreach($folderTree as $treeFolder)
                            @include('filesystem::partials.tree-node', ['folder' => $treeFolder, 'depth' => 0])
                        @endforeach
                    </div>
                </div>
            </nav>
        </flux:card>

        <!-- Cuota de Almacenamiento -->
        <flux:card class="p-4">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon name="cloud" class="text-zinc-400" />
                <flux:heading size="sm">Almacenamiento</flux:heading>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between text-xs font-medium">
                    <span class="text-zinc-500">{{ $stats['used_formatted'] }} de {{ $stats['quota_formatted'] }}</span>
                    <span class="{{ $stats['is_full'] ? 'text-red-500' : 'text-blue-500' }}">{{ $stats['percentage'] }}%</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden shadow-inner">
                    <div class="h-full transition-opacity duration-150 {{ $stats['is_full'] ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]' : 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]' }}" style="width: {{ $stats['percentage'] }}%"></div>
                </div>
                @if($stats['is_full'])
                    <div class="flex items-center gap-1.5 text-[10px] text-red-500 font-bold uppercase animate-pulse">
                        <flux:icon name="exclamation-triangle" variant="mini" />
                        Espacio casi agotado
                    </div>
                @endif
            </div>
        </flux:card>
    </aside>

    <!-- Panel Principal: Explorador -->
    <div class="lg:col-span-9 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Gestor de Archivos</flux:heading>
                <flux:subheading>Administra tus documentos y carpetas compartidas</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:modal.trigger name="create-folder">
                    <flux:button icon="folder-plus" variant="ghost">Nueva Carpeta</flux:button>
                </flux:modal.trigger>

                <div x-data="{ isUploading: false, progress: 0 }" 
                     x-on:livewire-upload-start="isUploading = true"
                     x-on:livewire-upload-finish="isUploading = false"
                     x-on:livewire-upload-error="isUploading = false; $wire.dispatch('toast', { message: 'Error: El archivo excede el límite del servidor o la conexión falló.', variant: 'danger' })"
                     x-on:livewire-upload-progress="progress = $event.detail.progress">
                    
                    <flux:button 
                        icon="cloud-arrow-up" 
                        variant="primary" 
                        onclick="document.getElementById('file-upload').click()"
                        x-bind:disabled="isUploading"
                    >
                        <span x-show="!isUploading">Subir Archivos</span>
                        <span x-show="isUploading" x-cloak>Subiendo... (<span x-text="progress"></span>%)</span>
                    </flux:button>

                    <input type="file" id="file-upload" wire:model="uploads" multiple class="hidden" accept=".jpg,.jpeg,.png,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.rar">
                    
                    @error('uploads.*') 
                        <p class="text-xs text-red-500 mt-1 animate-pulse">{{ $message }}</p> 
                    @enderror
                </div>
            </div>
        </div>

        <!-- Barra de Herramientas y Navegación -->
        <div class="flex flex-col gap-4 p-4 rounded-md bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar archivos en esta carpeta..." icon="magnifying-glass" class="max-w-md" />
                </div>
            </div>

            <!-- Breadcrumbs -->
            @if($viewMode === 'my_files')
            <div class="flex items-center gap-2 text-sm text-zinc-500 overflow-x-auto whitespace-nowrap pb-1">
                <button wire:click="navigateTo(null)" class="hover:text-blue-500 flex items-center gap-1 transition-opacity">
                    <flux:icon name="home" variant="mini" />
                    Raíz
                </button>
                @foreach($breadcrumbs as $crumb)
                    <flux:icon name="chevron-right" variant="mini" class="text-zinc-300 dark:text-zinc-700" />
                    <button wire:click="navigateTo({{ $crumb['id'] }})" class="hover:text-blue-500 transition-opacity">
                        {{ $crumb['name'] }}
                    </button>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Rejilla de Archivos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <!-- Carpetas -->
            @foreach($folders as $folder)
            <div class="group relative p-4 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:border-blue-300 dark:hover:border-blue-800 hover:shadow-md hover:shadow-blue-500/5 transition-opacity cursor-pointer"
                 wire:click="navigateTo({{ $folder->id }})">
                <div class="flex items-start justify-between">
                    <div class="p-2 rounded-md bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-opacity">
                        <flux:icon name="folder" class="w-8 h-8 text-blue-500 fill-blue-500/10" />
                    </div>
                    
                    <flux:dropdown @click.stop>
                        <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                        <flux:menu>
                            <flux:menu.item icon="share" wire:click.stop="share({{ $folder->id }}, 'folder')">Compartir</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click.stop="delete({{ $folder->id }}, 'folder')">Eliminar</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="mt-4">
                    <div class="font-semibold truncate text-zinc-800 dark:text-zinc-200" title="{{ $folder->name }}">{{ $folder->name }}</div>
                    <div class="text-xs text-zinc-500 mt-0.5">{{ $folder->children_count ?? 0 }} carpetas · {{ $folder->files_count ?? 0 }} archivos</div>
                </div>
            </div>
            @endforeach

            <!-- Archivos -->
            @foreach($files as $file)
            <div class="group relative p-4 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-opacity">
                <div class="flex items-start justify-between">
                    <div class="p-2 rounded-md bg-zinc-100 dark:bg-zinc-800 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700 transition-opacity">
                        @php
                            $icon = match(true) {
                                str_contains($file->mime_type, 'image') => 'photo',
                                str_contains($file->mime_type, 'pdf') => 'document-text',
                                str_contains($file->mime_type, 'video') => 'video-camera',
                                str_contains($file->mime_type, 'zip') || str_contains($file->mime_type, 'compressed') => 'archive-box',
                                default => 'document',
                            };
                        @endphp
                        <flux:icon name="{{ $icon }}" class="w-8 h-8 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    
                    <flux:dropdown @click.stop>
                        <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                        <flux:menu>
                            <flux:menu.item icon="arrow-down-tray" wire:click="download({{ $file->id }})">Descargar</flux:menu.item>
                            <flux:menu.item icon="share" wire:click="share({{ $file->id }}, 'file')">Compartir</flux:menu.item>
                            
                            @if(auth()->user()->can('filesystem.public.manage') || auth()->user()->hasAnyRole(['admin', 'wfm']))
                                <flux:menu.item icon="{{ $file->is_public ? 'eye-slash' : 'eye' }}" wire:click="togglePublic({{ $file->id }})">
                                    {{ $file->is_public ? 'Hacer Privado' : 'Hacer Público' }}
                                </flux:menu.item>
                            @endif

                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $file->id }}, 'file')">Eliminar</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="mt-4">
                    <div class="flex items-center gap-2">
                        <div class="font-medium truncate text-zinc-700 dark:text-zinc-300" title="{{ $file->name }}">{{ $file->name }}</div>
                        @if($file->is_public)
                            <flux:tooltip content="Público en Centro de Descargas">
                                <flux:icon name="globe-americas" variant="mini" class="text-blue-500" />
                            </flux:tooltip>
                        @endif
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <div class="text-[10px] text-zinc-500 font-mono">{{ $file->formatted_size }}</div>
                        <div class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-[9px] uppercase font-bold text-zinc-500 tracking-tighter">{{ $file->extension }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($folders->isEmpty() && $files->isEmpty())
        <div class="flex flex-col items-center justify-center py-32 bg-zinc-50/50 dark:bg-zinc-900/20 rounded-md border-2 border-dashed border-zinc-200 dark:border-zinc-800">
            <div class="relative">
                <flux:icon name="folder-open" class="w-20 h-20 text-zinc-200 dark:text-zinc-800" />
                <flux:icon name="magnifying-glass" variant="mini" class="absolute -bottom-1 -right-1 text-zinc-400" />
            </div>
            <p class="mt-4 text-zinc-500 font-medium">No se encontraron elementos en esta ubicación</p>
            <flux:button variant="ghost" size="sm" class="mt-2" wire:click="$set('search', '')">Limpiar filtros</flux:button>
        </div>
        @endif
    </div>

    <!-- Modales -->
    <flux:modal name="create-folder" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Carpeta</flux:heading>
                <flux:subheading>Crea un nuevo directorio para organizar tus archivos.</flux:subheading>
            </div>

            <flux:input wire:model="newFolderName" label="Nombre de la carpeta" placeholder="Ej. Documentos 2026" />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:click="createFolder">Crear Carpeta</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Compartir -->
    <flux:modal name="share-modal" class="min-w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Compartir "{{ $itemToShare['name'] ?? '' }}"</flux:heading>
                <flux:subheading>Permite que otros usuarios vean o editen este elemento.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model.live.debounce.300ms="userSearch" label="Buscar usuario" placeholder="Escribe el nombre del usuario..." />

                @if(!empty($this->users))
                <div class="border border-zinc-200 dark:border-zinc-800 rounded-md divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->users as $user)
                    <button wire:click="$set('shareTargetUserId', {{ $user->id }})" 
                            class="w-full flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $shareTargetUserId == $user->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <div class="flex items-center gap-3 text-left">
                            <flux:icon name="user-circle" class="w-8 h-8 text-zinc-400" />
                            <div>
                                <div class="font-medium text-sm">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                            </div>
                        </div>
                        @if($shareTargetUserId == $user->id)
                        <flux:icon name="check" variant="mini" class="text-blue-500" />
                        @endif
                    </button>
                    @endforeach
                </div>
                @endif

                <flux:select wire:model="shareAccessLevel" label="Nivel de Acceso">
                    <option value="view">Solo Ver</option>
                    <option value="edit">Editor</option>
                    <option value="admin">Administrador</option>
                </flux:select>
            </div>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="processShare" :disabled="!$shareTargetUserId">
                    Compartir
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
