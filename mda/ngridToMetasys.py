import csv
from datetime import datetime, date, time, timedelta
import argparse
import pandas as pd

def NGtoMet( ngridfile, metasysfile ):
  df = pd.read_csv( ngridfile, index_col=[1] )
  print( "bf ", len(df.index) );
  df.dropna( how='all', inplace=True )
  print( "af ", len(df.index) );
  df.index = pd.to_datetime( df.index, infer_datetime_format=True )
  df.sort_index( inplace=True )
  headings = df.columns.values
  print( headings )

  with open( metasysfile, mode='w', newline="", encoding='utf-8' ) as w:
    csvwriter = csv.writer( w )
    csvwriter.writerow( ['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'] )

    sum = {}

    for ngridline in df.itertuples():
      print( "bf ", ngridline )
      if ( not pd.isnull( ngridline[0] ) ) and ( not pd.isnull( ngridline[1] ) ) and ( not pd.isnull( ngridline[3] ) ):
        units = ngridline[3]
        print( "af ", ngridline )
        colname = ngridline[1] + '.' + units
        sumname = colname + ".sum"

        for index in range( 3, len( ngridline ) - 1 ):
          cell = ngridline[index+1]
          print( "bf ", cell )

          if not pd.isnull( cell ):
            print( "af ", cell )
            timesplit = headings[index].split( ':' )
            datesplit = ngridline[0].strftime( '%m/%d/%Y' ).split( '/' )
            dt = datetime( int( datesplit[2] ), int( datesplit[0] ), int( datesplit[1] ) )
            print( dt )

            if ( timesplit[0] == '24' ):
              dt += timedelta( days=1 )
              timesplit[0] = '00'

            dt += timedelta( hours=int( timesplit[0] ), minutes=int( timesplit[1] ) )
            timestamp = dt.strftime( '%m/%d/%Y %H:%M' )
            csvwriter.writerow( [ timestamp, '', colname, cell ] )

            if ( ( units == 'kWh' ) or ( units == 'kVAh' ) ):

              if ( sumname not in sum ):
                sum[sumname] = 0

              sum[sumname] += cell
              csvwriter.writerow( [ timestamp, '', sumname, sum[sumname] ] )

  return



if __name__ == "__main__":
  parser = argparse.ArgumentParser( description='converts NG output files to metasys filetype' )
  parser.add_argument( '-i', dest='input_file',  help='name of input file' )
  parser.add_argument( '-o', dest='output_file', help='name of output file' )
  args = parser.parse_args()
  NGtoMet( args.input_file,args.output_file )
