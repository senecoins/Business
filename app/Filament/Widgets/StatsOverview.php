<?php
namespace App\Filament\Widgets;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    protected static ?int $sort = 1;
    protected static ?string $maxHeight = '500px';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '10s';
    protected function getStats(): array
    {
        // Récupération des filtres de date de la page
        $filters = $this->filters;
        $startDate = isset($filters['startDate']) ? Carbon::parse($filters['startDate'])->startOfDay() : null;
        $endDate = isset($filters['endDate']) ? Carbon::parse($filters['endDate'])->endOfDay() : null;
        // Déterminer les périodes en fonction des filtres ou utiliser le mois courant/précédent par défaut
        $now = Carbon::now();
        if ($startDate && $endDate) {
            // Période courante basée sur les filtres
            $currentStart = $startDate->format('Y-m-d H:i:s');
            $currentEnd = $endDate->format('Y-m-d H:i:s');
            // Calculer la période précédente de même durée
            $daysDifference = $startDate->diffInDays($endDate);
            $previousStart = $startDate->copy()->subDays($daysDifference + 1)->format('Y-m-d H:i:s');
            $previousEnd = $startDate->copy()->subDay()->format('Y-m-d H:i:s');
        } else {
            // Utiliser le mois courant/précédent par défaut
            $currentStart = $now->copy()->startOfMonth()->format('Y-m-d H:i:s');
            $currentEnd = $now->copy()->endOfMonth()->format('Y-m-d H:i:s');
            $previousStart = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
            $previousEnd = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        }
        // 1. Calculer les statistiques de revenus (compatible SQLite)
        $salesQuery = Sale::where('payment_status', 'paid');
        $totalRevenue = clone $salesQuery;
        $totalRevenue = $this->applyDateFilter($totalRevenue, $startDate, $endDate)->sum('total_amount');
        $previousRevenue = Sale::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');
        $currentRevenue = Sale::where('payment_status', 'paid')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('total_amount');
        // Éviter la division par zéro
        $revenuePercentage = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : ($currentRevenue > 0 ? 100 : 0);
        // 2. Calculer le capital (valeur totale de l'inventaire des produits)
        $capital = Product::selectRaw('SUM(purchase_price * quantity) as total_capital')
            ->value('total_capital') ?? 0;
        // Obtenir le capital de la période précédente
        $previousCapital = Product::where('updated_at', '<', $currentStart)
            ->selectRaw('SUM(purchase_price * quantity) as total_capital')
            ->value('total_capital') ?? 0;
        // Éviter la division par zéro
        $capitalPercentage = $previousCapital > 0
            ? round((($capital - $previousCapital) / $previousCapital) * 100, 2)
            : ($capital > 0 ? 100 : 0);
        // 3. Calculer le profit (revenu - coût des marchandises vendues)
        $costOfGoodsQuery = SaleItem::whereHas('sale', function (Builder $query) {
            $query->where('payment_status', 'paid');
        });
        $totalCostOfGoodsSold = clone $costOfGoodsQuery;
        $totalCostOfGoodsSold = $this->applyDateFilterToSaleItems($totalCostOfGoodsSold, $startDate, $endDate)
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(products.purchase_price * sale_items.quantity) as total_cost')
            ->value('total_cost') ?? 0;
        $profit = $totalRevenue - $totalCostOfGoodsSold;
        // Calculer le profit de la période précédente
        $previousCostOfGoodsSold = SaleItem::whereHas('sale', function (Builder $query) use ($previousStart, $previousEnd) {
            $query->where('payment_status', 'paid')
                ->whereBetween('created_at', [$previousStart, $previousEnd]);
        })
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(products.purchase_price * sale_items.quantity) as total_cost')
            ->value('total_cost') ?? 0;
        $previousProfit = $previousRevenue - $previousCostOfGoodsSold;
        // Éviter la division par zéro
        $profitPercentage = $previousProfit != 0
            ? round((($profit - $previousProfit) / abs($previousProfit)) * 100, 2)
            : ($profit > 0 ? 100 : 0);
        // 4. Compter les alertes de stock bas
        $lowStockCount = Product::whereRaw('quantity <= security_stock')->count();
        // 5. Amélioration des données du graphique
        $chartScaleFactor = max(1, max($previousRevenue, $currentRevenue) / 100);
        $chartData = [
            round($previousRevenue / $chartScaleFactor, 2),
            round($currentRevenue / $chartScaleFactor, 2)
        ];
        // Obtenir des libellés descriptifs pour les périodes
        $currentPeriodLabel = $this->getPeriodLabel($startDate, $endDate);

        // Fonction pour formater les montants avec FCFA
        $formatMoney = function ($amount) {
            return number_format($amount, 0, ',', ' ') . ' FCFA';
        };

        return [
            Stat::make('Chiffre d\'affaires', $formatMoney($totalRevenue))
                ->description($currentPeriodLabel . ($revenuePercentage >= 0 ? ' +' . $revenuePercentage . '%' : ' -' . abs($revenuePercentage) . '%'))
                ->descriptionIcon($revenuePercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenuePercentage >= 0 ? Color::Green : Color::Rose)
                ->chart($chartData)
                ->icon('heroicon-o-banknotes'),
            Stat::make('Capital', $formatMoney($capital))
                ->description($currentPeriodLabel . ($capitalPercentage >= 0 ? ' +' . $capitalPercentage . '%' : ' -' . abs($capitalPercentage) . '%'))
                ->descriptionIcon($capitalPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($capitalPercentage >= 0 ? Color::Green : Color::Rose)
                ->icon('heroicon-o-building-library'),
            Stat::make('Bénéfice', $formatMoney($profit))
                ->description($currentPeriodLabel . ($profitPercentage >= 0 ? ' +' . $profitPercentage . '%' : ' -' . abs($profitPercentage) . '%'))
                ->descriptionIcon($profitPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitPercentage >= 0 ? Color::Green : Color::Rose)
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Alertes de stock', $lowStockCount)
                ->description('Produits en stock critique')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? Color::Orange : Color::Green)
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
    /**
     * Appliquer les filtres de date à une requête
     */
    protected function applyDateFilter(Builder $query, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('created_at', [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s')
            ]);
        }
        return $query;
    }
    /**
     * Appliquer les filtres de date aux SaleItems via les Sales
     */
    protected function applyDateFilterToSaleItems(Builder $query, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        if ($startDate && $endDate) {
            return $query->whereHas('sale', function (Builder $saleQuery) use ($startDate, $endDate) {
                $saleQuery->whereBetween('created_at', [
                    $startDate->format('Y-m-d H:i:s'),
                    $endDate->format('Y-m-d H:i:s')
                ]);
            });
        }
        return $query;
    }
    /**
     * Obtenir un libellé descriptif pour la période
     */
    protected function getPeriodLabel(?Carbon $startDate, ?Carbon $endDate): string
    {
        if ($startDate && $endDate) {
            if ($startDate->isSameDay($endDate)) {
                return 'Le ' . $startDate->format('d/m/Y');
            }
            return 'Du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y');
        }
        // Par défaut, on est sur le mois courant
        return 'Mois de ' . Carbon::now()->translatedFormat('F Y');
    }
    protected function getColumns(): int
    {
        return 4;
    }
}
