<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestor de Archivos</flux:heading>
            <flux:subheading>Administra tus documentos y carpetas compartidas</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:modal.trigger name="create-folder">
                <flux:button icon="folder-plus" variant="ghost">Nueva Carpeta</flux:button>
            </flux:modal.trigger>

            <flux:button icon="cloud-arrow-up" variant="primary" onclick="document.getElementById('file-upload').click()">
                Subir Archivos
            </flux:button>
            <input type="file" id="file-upload" wire:model="uploads" multiple class="hidden">
        </div>
    </div>

    <!-- Barra de Herramientas y Navegación -->
    <div class="flex flex-col gap-4 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
                <flux:navlist variant="pills" class="flex-none">
                    <flux:navlist.item wire:click="$set('viewMode', 'my_files')" :current="$viewMode === 'my_files'">Mis Archivos</flux:navlist.item>
                    <flux:navlist.item wire:click="$set('viewMode', 'shared')" :current="$viewMode === 'shared'">Compartidos</flux:navlist.item>
                </flux:navlist>
                
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar archivos..." icon="magnifying-glass" class="max-w-xs" />
            </div>

            <!-- Stats de almacenamiento -->
            <div class="flex items-center gap-3 text-sm">
                <div class="text-right">
                    <div class="font-medium {{ $stats['is_full'] ? 'text-red-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                        {{ $stats['used_formatted'] }} de {{ $stats['quota_formatted'] }}
                    </div>
                    <div class="text-xs text-zinc-500">Espacio utilizado</div>
                </div>
                <div class="w-24 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full {{ $stats['is_full'] ? 'bg-red-500' : 'bg-blue-500' }}" style="width: {{ $stats['percentage'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Breadcrumbs -->
        @if($viewMode === 'my_files')
        <div class="flex items-center gap-2 text-sm text-zinc-500">
            <button wire:click="navigateTo(null)" class="hover:text-blue-500 flex items-center gap-1">
                <flux:icon name="home" variant="mini" />
                Raíz
            </button>
            @foreach($breadcrumbs as $crumb)
                <flux:icon name="chevron-right" variant="mini" />
                <button wire:click="navigateTo({{ $crumb['id'] }})" class="hover:text-blue-500">
                    {{ $crumb['name'] }}
                </button>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Rejilla de Archivos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <!-- Carpetas -->
        @foreach($folders as $folder)
        <div class="group relative p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:shadow-md transition-all cursor-pointer"
             wire:click="navigateTo({{ $folder->id }})">
            <div class="flex items-start justify-between">
                <flux:icon name="folder" class="w-10 h-10 text-blue-500 fill-blue-500/20" />
                
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                    <flux:menu>
                        <flux:menu.item icon="share" wire:click="share({{ $folder->id }}, 'folder')">Compartir</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $folder->id }}, 'folder')">Eliminar</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
            <div class="mt-3">
                <div class="font-medium truncate" title="{{ $folder->name }}">{{ $folder->name }}</div>
                <div class="text-xs text-zinc-500">{{ $folder->files_count ?? 0 }} archivos</div>
            </div>
        </div>
        @endforeach

        <!-- Archivos -->
        @foreach($files as $file)
        <div class="group relative p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    @php
                        $icon = match(true) {
                            str_contains($file->mime_type, 'image') => 'photo',
                            str_contains($file->mime_type, 'pdf') => 'document-text',
                            str_contains($file->mime_type, 'video') => 'video-camera',
                            default => 'document',
                        };
                    @endphp
                    <flux:icon name="{{ $icon }}" class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                </div>
                
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                    <flux:menu>
                        <flux:menu.item icon="arrow-down-tray" wire:click="download({{ $file->id }})">Descargar</flux:menu.item>
                        <flux:menu.item icon="share" wire:click="share({{ $file->id }}, 'file')">Compartir</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $file->id }}, 'file')">Eliminar</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
            <div class="mt-3">
                <div class="font-medium truncate" title="{{ $file->name }}">{{ $file->name }}</div>
                <div class="flex justify-between items-center mt-1">
                    <div class="text-xs text-zinc-500">{{ $file->formatted_size }}</div>
                    <div class="text-[10px] uppercase font-bold text-zinc-400">{{ $file->extension }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($folders->isEmpty() && $files->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-zinc-500">
        <flux:icon name="folder-open" class="w-16 h-16 opacity-20 mb-4" />
        <p>No hay archivos en esta ubicación.</p>
    </div>
    @endif

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
                <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-800">
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
