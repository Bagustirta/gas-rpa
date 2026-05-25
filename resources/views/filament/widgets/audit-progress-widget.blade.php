@php
    $d = $liveData ?? [];
    $step = $d['step'] ?? 'idle';
    $isRunning = $d['is_running'] ?? false;
    $nikPct = $d['nik_pct'] ?? 0;
    $pangkalanPct = $d['pangkalan_pct'] ?? 0;

    $steps = ['login', 'navigate', 'filter', 'cek_nik'];
    $stepIndex = array_search($step, $steps) ?: 0;

    $borderColor = $isRunning ? '#3b82f6' : ($step === 'selesai' ? '#22c55e' : ($step === 'error' ? '#ef4444' : '#334155'));
@endphp

{{-- Single root element required by Livewire --}}
<div wire:poll.15s="checkHeartbeat">
@if(!empty($liveData))
<div style="
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    border: 1px solid {{ $borderColor }};
    border-radius: 16px;
    padding: 20px 24px;
    color: #f1f5f9;
    position: relative;
    overflow: hidden;
    box-shadow: 0 0 {{ $isRunning ? '20px rgba(59,130,246,0.25)' : '0px transparent' }};
    transition: all .4s ease;
">
    @if($isRunning)
    <div style="position:absolute;top:0;left:-100%;width:60%;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);animation:rpa-scan 2s linear infinite;"></div>
    <style>
        @keyframes rpa-scan { to { left:200%; } }
        @keyframes rpa-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }
    </style>
    @endif

    {{-- Header Row --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:24px;">
                @if($step==='login')🔐@elseif($step==='navigate')🧭@elseif($step==='filter')📅@elseif($step==='cek_nik')🔍@elseif($step==='selesai')✅@elseif($step==='error')❌@else🏪@endif
            </span>
            <div>
                <div style="font-size:10px;color:#64748b;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Bot RPA — Audit Kuota Pertamina</div>
                <div style="font-size:15px;font-weight:700;color:#f1f5f9;margin-top:2px;line-height:1.3;">
                    {{ $d['step_label'] ?? 'Memproses...' }}
                </div>
            </div>
        </div>
        <div style="
            display:flex;align-items:center;gap:6px;
            padding:5px 14px;border-radius:999px;font-size:11px;font-weight:800;
            background:{{ $isRunning ? 'rgba(59,130,246,.15)' : ($step==='selesai' ? 'rgba(34,197,94,.15)' : 'rgba(100,116,139,.15)') }};
            color:{{ $isRunning ? '#60a5fa' : ($step==='selesai' ? '#4ade80' : '#94a3b8') }};
            border:1px solid {{ $isRunning ? '#3b82f6' : ($step==='selesai' ? '#22c55e' : '#475569') }};
            white-space:nowrap;
        ">
            @if($isRunning)<span style="width:7px;height:7px;border-radius:50%;background:#3b82f6;display:inline-block;animation:rpa-dot 1.2s ease-in-out infinite;"></span> BERJALAN
            @elseif($step==='selesai') ✓ SELESAI
            @elseif($step==='error') ✗ ERROR
            @else IDLE @endif
        </div>
    </div>

    {{-- Pangkalan Progress Bar --}}
    @if(($d['pangkalan_total'] ?? 0) > 0)
    <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-size:12px;color:#94a3b8;">🏪 <strong style="color:#e2e8f0;">{{ $d['pangkalan_name'] ?? '-' }}</strong></span>
            <span style="font-size:12px;font-weight:700;color:#e2e8f0;">{{ $d['pangkalan_index'] }}/{{ $d['pangkalan_total'] }}</span>
        </div>
        <div style="background:#0f172a;border-radius:999px;height:5px;border:1px solid #334155;overflow:hidden;">
            <div style="height:100%;width:{{ $pangkalanPct }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:999px;"></div>
        </div>
    </div>
    @endif

    {{-- Step Pipeline --}}
    <div style="display:flex;align-items:center;margin-bottom:16px;">
        @foreach(['login'=>'🔐 Login','navigate'=>'🧭 Rekap','filter'=>'📅 Filter','cek_nik'=>'🔍 Cek NIK'] as $s=>$label)
        @php $done=in_array($s,array_slice($steps,0,$stepIndex)); $cur=$s===$step; @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <div style="
                width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;
                background:{{ $done?'#22c55e':($cur?'#3b82f6':'#1e293b') }};
                border:2px solid {{ $done?'#16a34a':($cur?'#60a5fa':'#334155') }};
                color:#fff;
                box-shadow:{{ $cur?'0 0 10px rgba(59,130,246,.5)':'none' }};
            ">{{ $done?'✓':explode(' ',$label)[0] }}</div>
            <span style="font-size:9px;font-weight:600;color:{{ $done?'#4ade80':($cur?'#60a5fa':'#475569') }};text-align:center;">
                {{ explode(' ',$label,2)[1]??'' }}
            </span>
        </div>
        @if(!$loop->last)<div style="flex:.4;height:2px;margin-bottom:18px;background:{{ $done?'#22c55e':'#334155' }};"></div>@endif
        @endforeach
    </div>

    {{-- NIK Progress --}}
    @if(($d['nik_total'] ?? 0) > 0)
    <div style="background:#0f172a;border:1px solid #334155;border-radius:12px;padding:12px 14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:7px;">
            <span style="font-size:12px;color:#94a3b8;">🔍 Cek NIK Pelanggan</span>
            <span style="font-size:12px;font-weight:700;">
                <span style="color:#60a5fa;">{{ number_format($d['nik_current']??0) }}</span>
                <span style="color:#475569;"> / {{ number_format($d['nik_total']??0) }}</span>
                <span style="color:#334155;font-size:10px;"> ({{ $nikPct }}%)</span>
            </span>
        </div>
        <div style="background:#1e293b;border-radius:999px;height:8px;overflow:hidden;">
            <div style="
                height:100%;width:{{ $nikPct }}%;
                background:linear-gradient(90deg,#3b82f6,#06b6d4);
                border-radius:999px;
                {{ $isRunning?'box-shadow:0 0 8px rgba(59,130,246,.6);':'' }}
            "></div>
        </div>
        @if($d['started_at'] ?? false)
        <div style="margin-top:8px;font-size:10px;color:#475569;">
            ⏱️ Dimulai: {{ $d['started_at'] }}
            @if($d['finished_at'] ?? false) &nbsp;·&nbsp; Selesai: {{ $d['finished_at'] }} @endif
        </div>
        @endif
    </div>
    @endif
</div>
@endif
</div>
