@extends('layouts.app')

@section('title', 'Books Collection')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Books Collection</h1>
        <p class="mt-2 text-gray-600">Browse and filter through our extensive collection of {{ number_format($books->total()) }} books</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('books.index') }}" class="space-y-4">
            <!-- Search Bar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search by title, author, ISBN, or publisher..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Categories -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categories</label>
                    <select name="categories[]" multiple size="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" 
                                {{ in_array($category->id, request('categories', [])) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="category_logic" value="OR" 
                                   {{ request('category_logic', 'OR') == 'OR' ? 'checked' : '' }}
                                   class="form-radio text-blue-600">
                            <span class="ml-2 text-sm">OR (any)</span>
                        </label>
                        <label class="inline-flex items-center ml-4">
                            <input type="radio" name="category_logic" value="AND" 
                                   {{ request('category_logic') == 'AND' ? 'checked' : '' }}
                                   class="form-radio text-blue-600">
                            <span class="ml-2 text-sm">AND (all)</span>
                        </label>
                    </div>
                </div>

                <!-- Author -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
                    <select name="author_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Authors</option>
                        @foreach($authors as $author)
                        <option value="{{ $author->id }}" {{ request('author_id') == $author->id ? 'selected' : '' }}>
                            {{ $author->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Availability -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                    <select name="availability" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="available" {{ request('availability') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="rented" {{ request('availability') == 'rented' ? 'selected' : '' }}>Rented</option>
                        <option value="reserved" {{ request('availability') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                    </select>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store Location</label>
                    <select name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                        <option value="{{ $location }}" {{ request('location') == $location ? 'selected' : '' }}>
                            {{ $location }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Publication Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Publication Year</label>
                    <div class="flex space-x-2">
                        <select name="year_from" class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">From</option>
                            @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year_from') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                        <select name="year_to" class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">To</option>
                            @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year_to') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Rating Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating Range</label>
                    <div class="flex space-x-2">
                        <input type="number" name="rating_from" min="1" max="10" step="0.1" 
                               value="{{ request('rating_from') }}" placeholder="From"
                               class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <input type="number" name="rating_to" min="1" max="10" step="0.1" 
                               value="{{ request('rating_to') }}" placeholder="To"
                               class="w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Sort Options -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="sort" value="rating" 
                               {{ request('sort', 'rating') == 'rating' ? 'checked' : '' }}
                               class="form-radio text-blue-600">
                        <span class="ml-2">Average Rating</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="sort" value="votes" 
                               {{ request('sort') == 'votes' ? 'checked' : '' }}
                               class="form-radio text-blue-600">
                        <span class="ml-2">Total Votes</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="sort" value="recent" 
                               {{ request('sort') == 'recent' ? 'checked' : '' }}
                               class="form-radio text-blue-600">
                        <span class="ml-2">Recent Popularity</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="sort" value="alphabetical" 
                               {{ request('sort') == 'alphabetical' ? 'checked' : '' }}
                               class="form-radio text-blue-600">
                        <span class="ml-2">Alphabetical</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex space-x-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i> Apply Filters
                </button>
                <a href="{{ route('books.index') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Books List -->
    <div class="space-y-4">
        @forelse($books as $book)
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-start">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900">{{ $book->title }}</h3>
                            <p class="text-gray-600 mt-1">by {{ $book->author->name }}</p>
                        </div>
                        @if($book->recent_rating && $book->previous_rating && $book->recent_rating > $book->previous_rating)
                        <span class="text-green-600 font-bold text-lg ml-2" title="Trending up!">
                            <i class="fas fa-arrow-trend-up"></i>
                        </span>
                        @endif
                    </div>
                    
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($book->categories as $category)
                        <span class="inline-block px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                            {{ $category->name }}
                        </span>
                        @endforeach
                    </div>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">ISBN:</span>
                            <span class="font-medium ml-1">{{ $book->isbn }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Publisher:</span>
                            <span class="font-medium ml-1">{{ $book->publisher }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Year:</span>
                            <span class="font-medium ml-1">{{ $book->publication_year }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Location:</span>
                            <span class="font-medium ml-1">{{ $book->store_location }}</span>
                        </div>
                    </div>
                </div>

                <div class="ml-6 text-right">
                    <div class="flex items-center justify-end space-x-2">
                        <div class="text-3xl font-bold text-blue-600">
                            {{ $book->avg_rating ? number_format($book->avg_rating, 1) : 'N/A' }}
                            @if($book->avg_rating)
                                <span class="text-lg text-gray-400">/10</span>
                            @endif
                        </div>

                        @if(!is_null($book->recent_rating) && !is_null($book->previous_rating))
                            @if($book->recent_rating > $book->previous_rating)
                                <i class="fas fa-arrow-up text-green-600 text-xl" title="Rating improved in last 7 days"></i>
                            @elseif($book->recent_rating < $book->previous_rating)
                                <i class="fas fa-arrow-down text-red-600 text-xl" title="Rating decreased in last 7 days"></i>
                            @else
                                <i class="fas fa-minus text-gray-400 text-xl" title="Rating unchanged"></i>
                            @endif
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-users mr-1"></i>
                        {{ number_format($book->total_votes) }} voters
                    </div>
                    <div class="mt-3">
                        @if($book->availability_status == 'available')
                        <span class="inline-block px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> Available
                        </span>
                        @elseif($book->availability_status == 'rented')
                        <span class="inline-block px-3 py-1 text-sm bg-yellow-100 text-yellow-800 rounded-full">
                            <i class="fas fa-clock mr-1"></i> Rented
                        </span>
                        @else
                        <span class="inline-block px-3 py-1 text-sm bg-red-100 text-red-800 rounded-full">
                            <i class="fas fa-bookmark mr-1"></i> Reserved
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-book-open text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No books found matching your criteria.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $books->appends(request()->query())->links() }}
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            // Initialize Select2 for all select elements
            $('select').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Custom placeholders for specific selects
            $('select[name="categories[]"]').select2({
                width: '100%',
                placeholder: 'Select one or more categories'
            });

            $('select[name="author_id"]').select2({
                width: '100%',
                placeholder: 'Choose an author'
            });

            $('select[name="availability"]').select2({
                width: '100%',
                placeholder: 'Select availability'
            });

            $('select[name="location"]').select2({
                width: '100%',
                placeholder: 'Select store location'
            });

            $('select[name="year_from"]').select2({
                width: '100%',
                placeholder: 'Year from'
            });

            $('select[name="year_to"]').select2({
                width: '100%',
                placeholder: 'Year to'
            });
        });
    </script>
@endpush

@endsection
