@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white focus:border-sky-500 focus:ring-sky-500']) }}>