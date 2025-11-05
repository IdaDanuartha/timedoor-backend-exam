@extends('layouts.app')

@section('title', 'Rate a Book')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Rate a Book</h1>
        <p class="mt-2 text-gray-600">Share your thoughts and help other readers discover great books</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <form method="POST" action="{{ route('ratings.store') }}" id="ratingForm">
            @csrf

            <!-- Notice -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                        <ul class="list-disc list-inside mt-2 text-sm text-blue-700 space-y-1">
                            <li>You can only submit one rating every 24 hours</li>
                            <li>Each book can only be rated once per user</li>
                            <li>Please select the author first, then choose their book</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Author -->
            <div class="mb-6">
                <label for="author_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Author <span class="text-red-500">*</span>
                </label>
                <select name="author_id" id="author_id" required
                        class="select2 w-full @error('author_id') border-red-500 @enderror">
                    <option value="">-- Choose an Author --</option>
                    @foreach($authors as $author)
                    <option value="{{ $author->id }}" {{ old('author_id') == $author->id ? 'selected' : '' }}>
                        {{ $author->name }}
                    </option>
                    @endforeach
                </select>
                @error('author_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Book -->
            <div class="mb-6">
                <label for="book_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Book <span class="text-red-500">*</span>
                </label>
                <select name="book_id" id="book_id" required disabled
                        class="select2 w-full bg-gray-100 @error('book_id') border-red-500 @enderror">
                    <option value="">-- Please select an author first --</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">
                    <i class="fas fa-lightbulb mr-1"></i> Books will appear after selecting an author
                </p>
                @error('book_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Rating -->
            <div class="mb-6">
                <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">
                    Your Rating <span class="text-red-500">*</span>
                </label>
                <select name="rating" id="rating" required class="select2 w-full @error('rating') border-red-500 @enderror">
                    <option value="">-- Choose a Rating --</option>
                    @for($i = 10; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ old('rating') == $i ? 'selected' : '' }}>
                        {{ $i }} - 
                        @if($i >= 9) Masterpiece
                        @elseif($i >= 8) Excellent
                        @elseif($i >= 7) Very Good
                        @elseif($i >= 6) Good
                        @elseif($i >= 5) Average
                        @elseif($i >= 4) Below Average
                        @elseif($i >= 3) Poor
                        @elseif($i >= 2) Very Poor
                        @else Terrible
                        @endif
                    </option>
                    @endfor
                </select>
                @error('rating')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Review -->
            <div class="mb-6">
                <label for="review" class="block text-sm font-medium text-gray-700 mb-2">
                    Your Review <span class="text-gray-400">(Optional)</span>
                </label>
                <textarea name="review" id="review" rows="4" maxlength="1000"
                          placeholder="Share your thoughts about this book..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('review') border-red-500 @enderror">{{ old('review') }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Maximum 1000 characters</p>
                @error('review')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex space-x-3">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Rating
                </button>
                <a href="{{ route('books.index') }}" 
                   class="px-6 py-3 bg-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    <i class="fas fa-times mr-2"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Initialize Select2 for all selects
    $('#author_id, #book_id, #rating').select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    const authorSelect = $('#author_id');
    const bookSelect = $('#book_id');

    authorSelect.on('change', function () {
        const authorId = $(this).val();

        if (!authorId) {
            bookSelect.prop('disabled', true)
                      .html('<option value="">-- Please select an author first --</option>')
                      .trigger('change');
            return;
        }

        // Show loading
        bookSelect.prop('disabled', true)
                  .html('<option value="">Loading books...</option>')
                  .trigger('change');

        // Fetch books dynamically
        $.ajax({
            url: `{{ route('books.by-author') }}`,
            data: { author_id: authorId },
            success: function (books) {
                if (books.length === 0) {
                    bookSelect.html('<option value="">No books found for this author</option>');
                } else {
                    bookSelect.html('<option value="">-- Choose a Book --</option>');
                    books.forEach(book => {
                        bookSelect.append(new Option(book.title, book.id));
                    });
                }
                bookSelect.prop('disabled', false).trigger('change');
            },
            error: function () {
                bookSelect.html('<option value="">Error loading books. Please try again.</option>');
            }
        });
    });

    // Trigger reload if form validation fails
    if (authorSelect.val()) authorSelect.trigger('change');
});
</script>
@endpush