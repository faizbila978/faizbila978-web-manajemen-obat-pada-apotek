<!-- resources/views/layouts/sidebar.blade.php -->
<aside class="w-64 min-h-screen bg-gradient-to-br from-[#0F766E] to-[#15803D] text-white p-4 flex flex-col gap-2 shadow-xl">
    <!-- Brand / Logo Area -->
    <div class="px-4 py-3 mb-4 font-bold text-xl tracking-wide flex items-center gap-2">
        <span>Apotek Farmasi 57</span>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex flex-col space-y-2">
        
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-2xl font-medium transition-all duration-200 
           {{ request()->routeIs('dashboard') ? 'bg-white text-[#0F766E] shadow-md' : 'text-emerald-100 hover:bg-white/10' }}">
            <span>Dashboard</span>
            @if(request()->routeIs('dashboard'))
                <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
            @endif
        </a>

        <!-- Stok & Mutasi -->
        <a href="{{ route('stok.index') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-2xl font-medium transition-all duration-200 
           {{ request()->routeIs('stok.*') ? 'bg-white text-[#0F766E] shadow-md' : 'text-emerald-100 hover:bg-white/10' }}">
            <span>Stok & Mutasi</span>
            @if(request()->routeIs('stok.*'))
                <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
            @endif
        </a>

        <!-- Data Master (Kategori & Jenis) -->
        <a href="{{ route('master.index') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-2xl font-medium transition-all duration-200 
           {{ request()->routeIs('master.*') ? 'bg-white text-[#0F766E] shadow-md' : 'text-emerald-100 hover:bg-white/10' }}">
            <span>Data Master</span>
            @if(request()->routeIs('master.*'))
                <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
            @endif
        </a>

        <!-- Skrining Alergi -->
        <a href="{{ route('skrining.index') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-2xl font-medium transition-all duration-200 
           {{ request()->routeIs('skrining.*') ? 'bg-white text-[#0F766E] shadow-md' : 'text-emerald-100 hover:bg-white/10' }}">
            <span>Skrining Alergi</span>
            @if(request()->routeIs('skrining.*'))
                <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
            @endif
        </a>

        <!-- Monitoring -->
        <a href="{{ route('monitoring.index') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-2xl font-medium transition-all duration-200 
           {{ request()->routeIs('monitoring.*') ? 'bg-white text-[#0F766E] shadow-md' : 'text-emerald-100 hover:bg-white/10' }}">
            <span>Monitoring</span>
            @if(request()->routeIs('monitoring.*'))
                <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
            @endif
        </a>

        <!-- Form Tombol Logout -->
        <form action="{{ route('logout') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="w-full text-left py-2 px-4 rounded-xl text-red-300 hover:bg-red-600/20 hover:text-red-200 transition flex items-center gap-2">
                <span>🚪</span> Logout
            </button>
        </form>

    </nav>
</aside>