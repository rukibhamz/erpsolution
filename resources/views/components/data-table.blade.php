@props([
    'headers' => [],
    'data' => [],
    'actions' => [],
    'searchable' => true,
    'sortable' => true,
    'paginated' => true,
    'exportable' => true,
    'bulkActions' => []
])

<div class="bg-white shadow-sm rounded-lg overflow-hidden" x-data="dataTable()">
    <!-- Table Header -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                @if($searchable)
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           x-model="search" 
                           @input.debounce.300ms="filterData()"
                           placeholder="Search..." 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                @endif

                @if(!empty($bulkActions))
                <div class="flex items-center space-x-2">
                    <input type="checkbox" 
                           x-model="selectAll" 
                           @change="toggleSelectAll()"
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <span class="text-sm text-gray-700">Select All</span>
                </div>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                @if($exportable)
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100" 
                         x-transition:enter-start="transform opacity-0 scale-95" 
                         x-transition:enter-end="transform opacity-100 scale-100" 
                         x-transition:leave="transition ease-in duration-75" 
                         x-transition:leave-start="transform opacity-100 scale-100" 
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export to PDF</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export to Excel</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export to CSV</a>
                    </div>
                </div>
                @endif

                <button @click="refreshData()" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Table Content -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @if(!empty($bulkActions))
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" 
                               x-model="selectAll" 
                               @change="toggleSelectAll()"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </th>
                    @endif
                    
                    @foreach($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        @if($sortable && isset($header['sortable']) && $header['sortable'])
                        <button @click="sort('{{ $header['key'] }}')" 
                                class="flex items-center space-x-1 hover:text-gray-700 focus:outline-none">
                            <span>{{ $header['label'] }}</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </button>
                        @else
                        {{ $header['label'] }}
                        @endif
                    </th>
                    @endforeach
                    
                    @if(!empty($actions))
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($data as $index => $row)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    @if(!empty($bulkActions))
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" 
                               x-model="selectedItems" 
                               value="{{ $index }}"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </td>
                    @endif
                    
                    @foreach($headers as $header)
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if(isset($header['type']) && $header['type'] === 'badge')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $row[$header['key']]['class'] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $row[$header['key']]['text'] ?? $row[$header['key']] }}
                        </span>
                        @elseif(isset($header['type']) && $header['type'] === 'image')
                        <img class="h-10 w-10 rounded-full object-cover" 
                             src="{{ $row[$header['key']] }}" 
                             alt="{{ $row['name'] ?? 'Image' }}">
                        @elseif(isset($header['type']) && $header['type'] === 'currency')
                        <span class="text-sm font-medium text-gray-900">
                            ₦{{ number_format($row[$header['key']], 2) }}
                        </span>
                        @elseif(isset($header['type']) && $header['type'] === 'date')
                        <span class="text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($row[$header['key']])->format('M d, Y') }}
                        </span>
                        @else
                        {{ $row[$header['key']] ?? '-' }}
                        @endif
                    </td>
                    @endforeach
                    
                    @if(!empty($actions))
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            @foreach($actions as $action)
                            @if(isset($action['condition']) && !$action['condition']($row))
                            @continue
                            @endif
                            
                            @if($action['type'] === 'link')
                            <a href="{{ $action['url']($row) }}" 
                               class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">
                                {{ $action['label'] }}
                            </a>
                            @elseif($action['type'] === 'button')
                            <button @click="{{ $action['action'] }}('{{ $index }}')" 
                                    class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">
                                {{ $action['label'] }}
                            </button>
                            @elseif($action['type'] === 'dropdown')
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100" 
                                     x-transition:enter-start="transform opacity-0 scale-95" 
                                     x-transition:enter-end="transform opacity-100 scale-100" 
                                     x-transition:leave="transition ease-in duration-75" 
                                     x-transition:leave-start="transform opacity-100 scale-100" 
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                    @foreach($action['items'] as $item)
                                    <a href="{{ $item['url']($row) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ $item['label'] }}
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($headers) + (!empty($bulkActions) ? 1 : 0) + (!empty($actions) ? 1 : 0) }}" 
                        class="px-6 py-12 text-center text-sm text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 mb-2">No data found</p>
                            <p class="text-gray-500">Get started by creating your first record.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($paginated && method_exists($data, 'links'))
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $data->links() }}
    </div>
    @endif
</div>

<script>
function dataTable() {
    return {
        search: '',
        sortField: '',
        sortDirection: 'asc',
        selectedItems: [],
        selectAll: false,
        
        filterData() {
            // Implement search functionality
            console.log('Searching for:', this.search);
        },
        
        sort(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            // Implement sort functionality
            console.log('Sorting by:', field, this.sortDirection);
        },
        
        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedItems = Array.from({length: {{ count($data) }}}, (_, i) => i);
            } else {
                this.selectedItems = [];
            }
        },
        
        refreshData() {
            // Implement refresh functionality
            window.location.reload();
        }
    }
}
</script>
