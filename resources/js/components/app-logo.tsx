import { useTranslation } from '@/hooks/use-translation';

/** The Rozine mark and name. The name is the product's, never the environment's APP_NAME. */
export default function AppLogo() {
    const { t } = useTranslation();

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-[#0039ff]">
                <img
                    src="/images/rozine-star-white.png"
                    alt=""
                    className="w-4.5 object-contain"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {t('common.brand.name')}
                </span>
            </div>
        </>
    );
}
