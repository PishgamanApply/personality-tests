<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- کارت‌های اطلاعات کلیدی اتصال برای ادمین --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <x-filament::section compact>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;" x-data="{ copied: false }">
                    <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280;">آدرس پایه وب‌سرویس‌ها (Base URL)</span>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <code style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: #4f46e5; word-break: break-all;">
                            {{ $this->getApiBaseUrl() }}
                        </code>
                        <x-filament::button
                            size="xs"
                            color="gray"
                            x-on:click="window.navigator.clipboard.writeText('{{ $this->getApiBaseUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        >
                            <span x-show="!copied">کپی</span>
                            <span x-show="copied" style="color: #10b981; font-weight: 700;">✓ کپی شد</span>
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section compact>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;" x-data="{ copied: false }">
                    <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280;">کلید احراز هویت سرور (X-Server-Key)</span>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <code style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: #d97706; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 180px;">
                            {{ Str::limit($this->getServerKey(), 24) }}
                        </code>
                        <x-filament::button
                            size="xs"
                            color="gray"
                            x-on:click="window.navigator.clipboard.writeText('{{ $this->getServerKey() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        >
                            <span x-show="!copied">کپی</span>
                            <span x-show="copied" style="color: #10b981; font-weight: 700;">✓ کپی شد</span>
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section compact>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280;">سامانه مبدأ مجاز (Source System)</span>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <x-filament::badge color="success" size="sm">
                            {{ $this->getSourceSystem() }}
                        </x-filament::badge>
                        <x-filament::button
                            tag="a"
                            href="{{ route('docs.guide') }}"
                            target="_blank"
                            size="xs"
                            color="gray"
                            icon="heroicon-o-arrow-top-right-on-square"
                            icon-position="after"
                        >
                            راهنما
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- بخش کنسول تعاملی Swagger --}}
        <x-filament::section
            heading="کنسول تعاملی تست و مستندات Swagger (OpenAPI 3.0.3)"
            description="مستندات استاندارد برای کاوش اندپوینت‌ها، مشاهده الگوها و اجرای تست‌های زنده"
        >
            <x-slot name="afterHeader">
                <x-filament::button
                    tag="a"
                    href="{{ route('docs.swagger') }}"
                    target="_blank"
                    size="sm"
                    color="primary"
                    icon="heroicon-o-arrow-top-right-on-square"
                    icon-position="after"
                >
                    مشاهده مجزا در تب مرورگر
                </x-filament::button>
            </x-slot>

            <div style="width: 100%; border-radius: 0.5rem; overflow: hidden; border: 1px solid #e5e7eb; background: #ffffff;">
                <iframe 
                    src="{{ route('docs.swagger', ['embedded' => 1]) }}" 
                    style="width: 100%; height: 85vh; min-height: 850px; border: none; display: block;" 
                    title="کنسول مستندات تعاملی Swagger"
                    loading="lazy"
                ></iframe>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
